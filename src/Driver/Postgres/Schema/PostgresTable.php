<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\Postgres\Schema;

use Spiral\Database\Driver\HandlerInterface;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractTable;

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
     *
     * SQLServer will reload schemas after successful savw.
     */
    public function save(int $operation = HandlerInterface::DO_ALL, bool $reset = true): void
    {
        parent::save($operation, $reset);

        if ($reset) {
            foreach ($this->fetchColumns() as $column) {
                $currentColumn = $this->current->findColumn($column->getName());
                if (!empty($currentColumn) && $column->compare($currentColumn)) {
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
        //Required for constraints fetch
        $tableOID = $this->driver->query('SELECT oid FROM pg_class WHERE relname = ?', [
            $this->getName(),
        ])->fetchColumn();

        $query = $this->driver->query(
            'SELECT *
                        FROM information_schema.columns
                        JOIN pg_type
                        ON (pg_type.typname = columns.udt_name)
                        WHERE table_name = ?',
            [$this->getName()]
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
                $this->getName(),
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
        $query = "SELECT * FROM pg_indexes WHERE schemaname = 'public' AND tablename = ?";

        $result = [];
        foreach ($this->driver->query($query, [$this->getName()]) as $schema) {
            $conType = $this->driver->query(
                'SELECT contype FROM pg_constraint WHERE conname = ?',
                [$schema['indexname']]
            )->fetchColumn();

            if ($conType == 'p') {
                //Skipping primary keys
                continue;
            }

            $result[] = PostgresIndex::createInstance($this->getName(), $schema);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchReferences(): array
    {
        //Mindblowing
        $query = 'SELECT tc.constraint_name, tc.table_name, kcu.column_name, rc.update_rule, '
            . 'rc.delete_rule, ccu.table_name AS foreign_table_name, '
            . "ccu.column_name AS foreign_column_name\n"
            . "FROM information_schema.table_constraints AS tc\n"
            . "JOIN information_schema.key_column_usage AS kcu\n"
            . "   ON tc.constraint_name = kcu.constraint_name\n"
            . "JOIN information_schema.constraint_column_usage AS ccu\n"
            . "   ON ccu.constraint_name = tc.constraint_name\n"
            . "JOIN information_schema.referential_constraints AS rc\n"
            . "   ON rc.constraint_name = tc.constraint_name\n"
            . "WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name = ?";

        $fks = [];
        foreach ($this->driver->query($query, [$this->getName()]) as $schema) {
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
            $result[] = PostgresForeign::createInstance(
                $this->getName(),
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
        $query = "SELECT * FROM pg_indexes WHERE schemaname = 'public' AND tablename = ?";

        foreach ($this->driver->query($query, [$this->getName()]) as $schema) {
            $conType = $this->driver->query(
                'SELECT contype FROM pg_constraint WHERE conname = ?',
                [$schema['indexname']]
            )->fetchColumn();

            if ($conType != 'p') {
                //Skipping primary keys
                continue;
            }

            //To simplify definitions
            $index = PostgresIndex::createInstance($this->getName(), $schema);

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
        return new PostgresColumn($this->getName(), $name, $this->driver->getTimezone());
    }

    /**
     * {@inheritdoc}
     */
    protected function createIndex(string $name): AbstractIndex
    {
        return new PostgresIndex($this->getName(), $name);
    }

    /**
     * {@inheritdoc}
     */
    protected function createForeign(string $name): AbstractForeignKey
    {
        return new PostgresForeign($this->getName(), $this->getPrefix(), $name);
    }
}
