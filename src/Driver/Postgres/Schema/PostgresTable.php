<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
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
     *
     * @var array
     */
    private $sequences = [];

    /**
     * Sequence object name usually defined only for primary keys and required by ORM to correctly
     * resolve inserted row id.
     *
     * @var string|null
     */
    private $primarySequence = null;

    /**
     * Sequence object name usually defined only for primary keys and required by ORM to correctly
     * resolve inserted row id.
     *
     * @return string|null
     */
    public function getSequence()
    {
        return $this->primarySequence;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->removeSchemaFromTableName($this->getFullName());
    }

    /**
     * {@inheritdoc}
     *
     * SQLServer will reload schemas after successful savw.
     */
    public function save(int $operation = HandlerInterface::DO_ALL, bool $reset = true): void
    {
        parent::save($operation, $reset);

        if (!$reset) {
            foreach ($this->fetchColumns() as $column) {
                $currentColumn = $this->current->findColumn($column->getName());
                if ($currentColumn !== null && $column->compare($currentColumn)) {
                    //Ensure constrained columns
                    $this->current->registerColumn($column);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
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
            [$tableSchema, $tableName]
        )->fetchColumn();

        $query = $this->driver->query(
            'SELECT *
                FROM information_schema.columns
                JOIN pg_type
                    ON (pg_type.typname = columns.udt_name)
                WHERE table_schema = ?
                AND table_name = ?',
            [$tableSchema, $tableName]
        );

        $result = [];
        foreach ($query->fetchAll() as $schema) {
            $name = $schema['column_name'];
            if (
                is_string($schema['column_default'])
                && preg_match(
                    '/^nextval\([\'"]([a-z0-9_"]+)[\'"](?:::regclass)?\)$/i',
                    $schema['column_default'],
                    $matches
                )
            ) {
                //Column is sequential
                $this->sequences[$name] = $matches[1];
            }

            $result[] = PostgresColumn::createInstance(
                $tableSchema . '.' . $tableName,
                $schema + ['tableOID' => $tableOID],
                $this->driver
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchIndexes(bool $all = false): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        $query = 'SELECT * FROM pg_indexes WHERE schemaname = ? AND tablename = ?';

        $result = [];
        foreach ($this->driver->query($query, [$tableSchema, $tableName]) as $schema) {
            $conType = $this->driver->query(
                'SELECT contype FROM pg_constraint WHERE conname = ?',
                [$schema['indexname']]
            )->fetchColumn();

            if ($conType === 'p') {
                //Skipping primary keys
                continue;
            }

            $result[] = PostgresIndex::createInstance($tableSchema . '.' . $tableName, $schema);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
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
                $schema
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchPrimaryKeys(): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        $query = 'SELECT * FROM pg_indexes WHERE schemaname = ? AND tablename = ?';

        foreach ($this->driver->query($query, [$tableSchema, $tableName]) as $schema) {
            $conType = $this->driver->query(
                'SELECT contype FROM pg_constraint WHERE conname = ?',
                [$schema['indexname']]
            )->fetchColumn();

            if ($conType !== 'p') {
                //Skipping primary keys
                continue;
            }

            //To simplify definitions
            $index = PostgresIndex::createInstance($tableSchema . '.' . $tableName, $schema);

            if (is_array($this->primarySequence) && count($index->getColumns()) === 1) {
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
     * {@inheritdoc}
     */
    protected function createColumn(string $name): AbstractColumn
    {
        return new PostgresColumn(
            $this->getNormalizedTableName(),
            $this->removeSchemaFromTableName($name),
            $this->driver->getTimezone()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createIndex(string $name): AbstractIndex
    {
        return new PostgresIndex(
            $this->getNormalizedTableName(),
            $this->removeSchemaFromTableName($name)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createForeign(string $name): AbstractForeignKey
    {
        return new PostgresForeignKey(
            $this->getNormalizedTableName(),
            $this->getPrefix(),
            $this->removeSchemaFromTableName($name)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function prefixTableName(string $name): string
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($name);

        $name = parent::prefixTableName($name);

        return $schema ? $schema . '.' . $name : $name;
    }

    protected function getNormalizedTableName(): string
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($this->getFullName());

        return $schema . '.' . $name;
    }

    protected function removeSchemaFromTableName(string $name): string
    {
        if (strpos($name, '.') !== false) {
            [, $name] = explode('.', $name, 2);
        }

        return $name;
    }
}
