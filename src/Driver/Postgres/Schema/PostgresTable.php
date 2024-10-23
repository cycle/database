<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Schema;

use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;

/**
 * @property PostgresDriver $driver
 */
class PostgresTable extends AbstractTable
{
    /**
     * Found table sequences.
     */
    private array $sequences = [];

    /**
     * Sequence object name usually defined only for primary keys and required by ORM to correctly
     * resolve inserted row id.
     */
    private ?string $primarySequence = null;

    /**
     * Sequence object name usually defined only for primary keys and required by ORM to correctly
     * resolve inserted row id.
     */
    public function getSequence(): ?string
    {
        return $this->primarySequence;
    }

    public function getName(): string
    {
        return $this->removeSchemaFromTableName($this->getFullName());
    }

    /**
     * SQLServer will reload schemas after successful save.
     */
    public function save(int $operation = HandlerInterface::DO_ALL, bool $reset = true): void
    {
        parent::save($operation, $reset);

        if ($reset) {
            foreach ($this->fetchColumns() as $column) {
                $currentColumn = $this->current->findColumn($column->getName());
                if ($currentColumn !== null && $column->compare($currentColumn)) {
                    //Ensure constrained columns
                    $this->current->registerColumn($column);
                }
            }
        }
    }

    public function getDependencies(): array
    {
        $tables = [];
        foreach ($this->current->getForeignKeys() as $foreignKey) {
            [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($foreignKey->getForeignTable());
            $tables[] = $tableSchema . '.' . $tableName;
        }

        return $tables;
    }

    protected function fetchColumns(): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        //Required for constraints fetch
        $tableOID = $this->driver->query(
            'SELECT pgc.oid
                FROM pg_class as pgc
                JOIN pg_namespace as pgn
                    ON (pgn.oid = pgc.relnamespace)
                WHERE pgn.nspname = ?
                AND pgc.relname = ?',
            [$tableSchema, $tableName],
        )->fetchColumn();

        $query = $this->driver->query(
            'SELECT *
                FROM information_schema.columns
                JOIN pg_type
                    ON (pg_type.typname = columns.udt_name)
                WHERE table_schema = ?
                AND table_name = ?',
            [$tableSchema, $tableName],
        );

        $primaryKeys = \array_column($this->driver->query(
            'SELECT key_column_usage.column_name
                FROM information_schema.table_constraints
                JOIN information_schema.key_column_usage
                    ON (
                            key_column_usage.table_name = table_constraints.table_name AND
                            key_column_usage.table_schema = table_constraints.table_schema AND
                            key_column_usage.constraint_name = table_constraints.constraint_name
                        )
                WHERE table_constraints.constraint_type = \'PRIMARY KEY\' AND
                      key_column_usage.ordinal_position IS NOT NULL AND
                      table_constraints.table_schema = ? AND
                      table_constraints.table_name = ?',
            [$tableSchema, $tableName],
        )->fetchAll(), 'column_name');

        $result = [];
        foreach ($query->fetchAll() as $schema) {
            $name = $schema['column_name'];
            if (
                \is_string($schema['column_default'])
                && \preg_match(
                    '/^nextval\([\'"]([a-z0-9_"]+)[\'"](?:::regclass)?\)$/i',
                    $schema['column_default'],
                    $matches,
                )
            ) {
                //Column is sequential
                $this->sequences[$name] = $matches[1];
            }

            $schema['is_primary'] = \in_array($schema['column_name'], $primaryKeys, true);

            $result[] = PostgresColumn::createInstance(
                $tableSchema . '.' . $tableName,
                $schema + ['tableOID' => $tableOID],
                $this->driver,
            );
        }

        return $result;
    }

    protected function fetchIndexes(bool $all = false): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        $query = <<<SQL
            SELECT i.indexname, i.indexdef, c.contype
            FROM pg_indexes i
            LEFT JOIN pg_namespace ns
                ON nspname = i.schemaname
            LEFT JOIN pg_constraint c
                ON c.conname = i.indexname
                AND c.connamespace = ns.oid
            WHERE i.schemaname = ? AND i.tablename = ?
            SQL;

        $result = [];
        foreach ($this->driver->query($query, [$tableSchema, $tableName]) as $schema) {
            if ($schema['contype'] === 'p') {
                //Skipping primary keys
                continue;
            }
            $result[] = PostgresIndex::createInstance($tableSchema . '.' . $tableName, $schema);
        }

        return $result;
    }

    protected function fetchReferences(): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        //Mindblowing
        $query = 'SELECT tc.constraint_name, tc.constraint_schema, tc.table_name, kcu.column_name, rc.update_rule, '
            . 'rc.delete_rule, ccu.table_name AS foreign_table_name, '
            . "ccu.column_name AS foreign_column_name\n"
            . "FROM information_schema.table_constraints AS tc\n"
            . "JOIN information_schema.key_column_usage AS kcu\n"
            . "   ON tc.constraint_name = kcu.constraint_name\n"
            . "JOIN information_schema.constraint_column_usage AS ccu\n"
            . "   ON ccu.constraint_name = tc.constraint_name\n"
            . "JOIN information_schema.referential_constraints AS rc\n"
            . "   ON rc.constraint_name = tc.constraint_name\n"
            . "WHERE constraint_type = 'FOREIGN KEY' AND tc.table_schema = ? AND tc.table_name = ?";

        $fks = [];
        foreach ($this->driver->query($query, [$tableSchema, $tableName]) as $schema) {
            if (!isset($fks[$schema['constraint_name']])) {
                $fks[$schema['constraint_name']] = $schema;
                $fks[$schema['constraint_name']]['column_name'] = [$schema['column_name']];
                $fks[$schema['constraint_name']]['foreign_column_name'] = [$schema['foreign_column_name']];
                continue;
            }

            $fks[$schema['constraint_name']]['column_name'][] = $schema['column_name'];
            $fks[$schema['constraint_name']]['foreign_column_name'][] = $schema['foreign_column_name'];
        }

        $result = [];
        foreach ($fks as $schema) {
            $result[] = PostgresForeignKey::createInstance(
                $tableSchema . '.' . $tableName,
                $this->getPrefix(),
                $schema,
            );
        }

        return $result;
    }

    protected function fetchPrimaryKeys(): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        $query = <<<SQL
            SELECT i.indexname, i.indexdef, c.contype
            FROM pg_indexes i
            INNER JOIN pg_namespace ns
                ON nspname = i.schemaname
            INNER JOIN pg_constraint c
                ON c.conname = i.indexname
                AND c.connamespace = ns.oid
            WHERE i.schemaname = ? AND i.tablename = ?
              AND c.contype = 'p'
            SQL;

        foreach ($this->driver->query($query, [$tableSchema, $tableName]) as $schema) {
            //To simplify definitions
            $index = PostgresIndex::createInstance($tableSchema . '.' . $tableName, $schema);

            if (\is_array($this->primarySequence) && \count($index->getColumns()) === 1) {
                $column = $index->getColumns()[0];

                if (isset($this->sequences[$column])) {
                    //We found our primary sequence
                    $this->primarySequence = $this->sequences[$column];
                }
            }

            return $index->getColumns();
        }

        return [];
    }

    /**
     * @psalm-param non-empty-string $name
     */
    protected function createColumn(string $name): AbstractColumn
    {
        return new PostgresColumn(
            $this->getNormalizedTableName(),
            $this->removeSchemaFromTableName($name),
            $this->driver->getTimezone(),
        );
    }

    /**
     * @psalm-param non-empty-string $name
     */
    protected function createIndex(string $name): AbstractIndex
    {
        return new PostgresIndex(
            $this->getNormalizedTableName(),
            $this->removeSchemaFromTableName($name),
        );
    }

    /**
     * @psalm-param non-empty-string $name
     */
    protected function createForeign(string $name): AbstractForeignKey
    {
        return new PostgresForeignKey(
            $this->getNormalizedTableName(),
            $this->getPrefix(),
            $this->removeSchemaFromTableName($name),
        );
    }

    /**
     * @psalm-param non-empty-string $name
     */
    protected function prefixTableName(string $name): string
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($name);

        return $schema . '.' . parent::prefixTableName($name);
    }

    /**
     * Get table name with schema. If table doesn't contain schema, schema will be added from config
     */
    protected function getNormalizedTableName(): string
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($this->getFullName());

        return $schema . '.' . $name;
    }

    /**
     * Return table name without schema
     *
     * @psalm-param non-empty-string $name
     */
    protected function removeSchemaFromTableName(string $name): string
    {
        if (\str_contains($name, '.')) {
            [, $name] = \explode('.', $name, 2);
        }

        return $name;
    }
}
