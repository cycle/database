<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\State;

class MySQLTable extends AbstractTable
{
    /**
     * List of most common MySQL table engines.
     */
    public const ENGINE_INNODB = 'InnoDB';

    public const ENGINE_MYISAM = 'MyISAM';
    public const ENGINE_MEMORY = 'Memory';

    /**
     * MySQL table engine.
     */
    private string $engine = self::ENGINE_INNODB;

    /**
     * MySQL version.
     */
    private ?string $version = null;

    /**
     * Change table engine. Such operation will be applied only at moment of table creation.
     *
     * @psalm-param non-empty-string $engine
     *
     * @throws SchemaException
     */
    public function setEngine(string $engine): self
    {
        if ($this->exists()) {
            throw new SchemaException('Table engine can be set only at moment of creation');
        }

        $this->engine = $engine;

        return $this;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Populate table schema with values from database.
     */
    protected function initSchema(State $state): void
    {
        parent::initSchema($state);

        //Reading table schema
        $this->engine = $this->driver->query(
            'SHOW TABLE STATUS WHERE `Name` = ?',
            [
                $state->getName(),
            ],
        )->fetch()['Engine'];
    }

    protected function isIndexColumnSortingSupported(): bool
    {
        if (!$this->version) {
            $this->version = $this->driver->query('SELECT VERSION() AS version')->fetch()['version'];
        }

        if (\str_contains($this->version, 'MariaDB')) {
            return false;
        }

        return \version_compare($this->version, '8.0', '>=');
    }

    protected function fetchColumns(): array
    {
        $query = "SHOW FULL COLUMNS FROM {$this->driver->identifier($this->getFullName())}";

        $result = [];
        foreach ($this->driver->query($query) as $schema) {
            $result[] = MySQLColumn::createInstance(
                $this->getFullName(),
                $schema,
                $this->driver->getTimezone(),
            );
        }

        return $result;
    }

    protected function fetchIndexes(): array
    {
        $query = "SHOW INDEXES FROM {$this->driver->identifier($this->getFullName())}";

        //Gluing all index definitions together
        $schemas = [];
        foreach ($this->driver->query($query) as $index) {
            if ($index['Key_name'] === 'PRIMARY') {
                //Skipping PRIMARY index
                continue;
            }

            $schemas[$index['Key_name']][] = $index;
        }

        $result = [];
        foreach ($schemas as $name => $index) {
            $result[] = MySQLIndex::createInstance($this->getFullName(), $name, $index);
        }

        return $result;
    }

    protected function fetchReferences(): array
    {
        $references = $this->driver->query(
            'SELECT * FROM `information_schema`.`referential_constraints`
            WHERE `constraint_schema` = ? AND `table_name` = ?',
            [$this->driver->getSource(), $this->getFullName()],
        );

        $result = [];
        foreach ($references as $schema) {
            $columns = $this->driver->query(
                'SELECT * FROM `information_schema`.`key_column_usage`
                WHERE `constraint_name` = ? AND `table_schema` = ? AND `table_name` = ?',
                [$schema['CONSTRAINT_NAME'], $this->driver->getSource(), $this->getFullName()],
            )->fetchAll();

            $schema['COLUMN_NAME'] = [];
            $schema['REFERENCED_COLUMN_NAME'] = [];

            foreach ($columns as $column) {
                $schema['COLUMN_NAME'][] = $column['COLUMN_NAME'];
                $schema['REFERENCED_COLUMN_NAME'][] = $column['REFERENCED_COLUMN_NAME'];
            }

            $result[] = MySQLForeignKey::createInstance(
                $this->getFullName(),
                $this->getPrefix(),
                $schema,
            );
        }

        return $result;
    }

    /**
     * Fetching primary keys from table.
     */
    protected function fetchPrimaryKeys(): array
    {
        $query = "SHOW INDEXES FROM {$this->driver->identifier($this->getFullName())}";

        $primaryKeys = [];
        foreach ($this->driver->query($query) as $index) {
            if ($index['Key_name'] === 'PRIMARY') {
                $primaryKeys[] = $index['Column_name'];
            }
        }

        return $primaryKeys;
    }

    /**
     * @psalm-param non-empty-string $name
     */
    protected function createColumn(string $name): AbstractColumn
    {
        return new MySQLColumn($this->getFullName(), $name, $this->driver->getTimezone());
    }

    /**
     * @psalm-param non-empty-string $name
     */
    protected function createIndex(string $name): AbstractIndex
    {
        return new MySQLIndex($this->getFullName(), $name);
    }

    /**
     * @psalm-param non-empty-string $name
     */
    protected function createForeign(string $name): AbstractForeignKey
    {
        return new MySQLForeignKey($this->getFullName(), $this->getPrefix(), $name);
    }
}
