<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\Oracle\Schema;

use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;

class OracleTable extends AbstractTable
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->removeSchemaFromTableName($this->getFullName());
    }

    protected function fetchColumns(): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        $query = $this->driver->query(
            'select col.column_id, 
                           col.column_name, 
                           col.data_type, 
                           col.data_length, 
                           col.data_default, 
                           col.data_precision, 
                           col.data_scale, 
                           col.nullable
                    from ALL_TAB_COLUMNS col
                    inner join sys.all_tables t on col.owner = t.owner and col.table_name = t.table_name
                    where col.owner = ?
                    and col.table_name = ?
                    order by col.column_id',
            [$tableSchema, $tableName]
        );

        $result = [];
        foreach ($query->fetchAll() as $schema) {
            $result[] = OracleColumn::createInstance($tableSchema . '.' . $tableName, $schema, $this->driver);
        }

        return $result;
    }

    protected function fetchIndexes(): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        $query = $this->driver->query(
            'select ALL_INDEXES.INDEX_NAME, ALL_IND_COLUMNS.COLUMN_NAME, ALL_IND_COLUMNS.DESCEND, 
                            ALL_INDEXES.UNIQUENESS
                    from ALL_INDEXES
                    join ALL_IND_COLUMNS
                        on ALL_INDEXES.INDEX_NAME = ALL_IND_COLUMNS.INDEX_NAME
                        and ALL_INDEXES.OWNER = ALL_IND_COLUMNS.INDEX_OWNER
                    where ALL_INDEXES.owner = ?
                    and ALL_INDEXES.table_name = ?
                    and ALL_INDEXES.table_type = ?
                    order by ALL_IND_COLUMNS.COLUMN_POSITION',
            [$tableSchema, $tableName, 'TABLE']
        );

        //Gluing all index definitions together
        $schemas = [];
        foreach ($query as $index) {
            $schemas[$index['INDEX_NAME']][] = $index;
        }

        $result = [];
        foreach ($schemas as $name => $index) {
            $result[] = OracleIndex::createInstance($this->getFullName(), $name, $index);
        }

        return $result;
    }

    protected function fetchReferences(): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        $query = $this->driver->query(
            'SELECT a.table_name, a.column_name, a.constraint_name, c.owner, c.delete_rule, 
                       -- referenced pk
                       c.r_owner, c_pk.table_name r_table_name, c_pk.constraint_name r_pk, a_pk.column_name r_column_name
                      FROM all_cons_columns a
                      JOIN all_constraints c ON a.owner = c.owner AND a.constraint_name = c.constraint_name
                      JOIN all_constraints c_pk ON c.r_owner = c_pk.owner AND c.r_constraint_name = c_pk.constraint_name
                      JOIN all_cons_columns a_pk ON a_pk.owner = c_pk.owner AND a_pk.constraint_name = c_pk.constraint_name
                    where a.owner = ?
                    and a.table_name = ?
                    and c.constraint_type = ?
                    order by a.table_name, a.position',
            [$tableSchema, $tableName, 'R']
        );

        $result = [];
        foreach ($query as $index) {
            $result[] = OracleForeignKey::createInstance(
                $this->getFullName(),
                $this->getPrefix(),
                $index
            );
        }

        return $result;
    }

    protected function fetchPrimaryKeys(): array
    {
        [$tableSchema, $tableName] = $this->driver->parseSchemaAndTable($this->getFullName());

        $query = $this->driver->query(
            'select *
                    from ALL_CONSTRAINTS
                    join ALL_CONS_COLUMNS 
                        on ALL_CONS_COLUMNS.CONSTRAINT_NAME = ALL_CONSTRAINTS.CONSTRAINT_NAME
                        and ALL_CONS_COLUMNS.OWNER = ALL_CONSTRAINTS.OWNER
                    where ALL_CONSTRAINTS.owner = ?
                    and ALL_CONSTRAINTS.table_name = ?
                    and ALL_CONSTRAINTS.CONSTRAINT_TYPE = ?
                    order by ALL_CONS_COLUMNS.TABLE_NAME, ALL_CONS_COLUMNS.POSITION',
            [$tableSchema, $tableName, 'P']
        );

        $primaryKeys = [];
        foreach ($query as $index) {
            $primaryKeys[] = $index['COLUMN_NAME'];
        }

        return $primaryKeys;
    }

    protected function createColumn(string $name): AbstractColumn
    {
        return new OracleColumn(
            $this->getNormalizedTableName(),
            $this->removeSchemaFromTableName($name),
            $this->driver->getTimezone()
        );
    }

    protected function createIndex(string $name): AbstractIndex
    {
        return new OracleIndex($this->getFullName(), $name);
    }

    protected function createForeign(string $name): AbstractForeignKey
    {
        return new OracleForeignKey($this->getFullName(), $this->getPrefix(), $name);
    }

    /**
     * {@inheritdoc}
     */
    protected function prefixTableName(string $name): string
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($name);

        return $schema . '.' . parent::prefixTableName($name);
    }

    /**
     * Get table name with schema. If table doesn't contain schema, schema will be added from config
     *
     * @return string
     */
    protected function getNormalizedTableName(): string
    {
        [$schema, $name] = $this->driver->parseSchemaAndTable($this->getFullName());

        return $schema . '.' . $name;
    }

    /**
     * Return table name without schema
     *
     * @param string $name
     * @return string
     */
    protected function removeSchemaFromTableName(string $name): string
    {
        if (strpos($name, '.') !== false) {
            [, $name] = explode('.', $name, 2);
        }

        return $name;
    }
}
