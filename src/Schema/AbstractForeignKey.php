<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\ForeignKeyInterface;
use Cycle\Database\Schema\Traits\ElementTrait;
use Spiral\Database\Driver\DriverInterface as SpiralDriverInterface;
use Spiral\Database\Schema\AbstractForeignKey as SpiralAbstractForeignKey;

interface_exists(SpiralDriverInterface::class);
class_exists(SpiralAbstractForeignKey::class);

/**
 * Abstract foreign schema with read (see ReferenceInterface) and write abilities. Must be
 * implemented by driver to support DBMS specific syntax and creation rules.
 */
abstract class AbstractForeignKey implements ForeignKeyInterface, ElementInterface
{
    use ElementTrait;

    /**
     * Parent table isolation prefix.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * Local column name (key name).
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Referenced table name (including prefix).
     *
     * @var string
     */
    protected $foreignTable = '';

    /**
     * Linked foreign key name (foreign column).
     *
     * @var array
     */
    protected $foreignKeys = [];

    /**
     * Action on foreign column value deletion.
     *
     * @var string
     */
    protected $deleteRule = self::NO_ACTION;

    /**
     * Action on foreign column value update.
     *
     * @var string
     */
    protected $updateRule = self::NO_ACTION;

    /**
     * @param string $table
     * @param string $tablePrefix
     * @param string $name
     */
    public function __construct(string $table, string $tablePrefix, string $name)
    {
        $this->table = $table;
        $this->name = $name;
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteRule(): string
    {
        return $this->deleteRule;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateRule(): string
    {
        return $this->updateRule;
    }

    /**
     * Set local column names foreign key relates to. Make sure column type is the same as foreign
     * column one.
     *
     * @param array $columns
     * @return self
     */
    public function columns(array $columns): AbstractForeignKey
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Set foreign table name and key local column must reference to. Make sure local and foreign
     * column types are identical.
     *
     * @param string $table       Foreign table name with or without database prefix (see 3rd
     *                            argument).
     * @param array  $columns     Foreign key names (id by default).
     * @param bool   $forcePrefix When true foreign table will get same prefix as table being
     *                            modified.
     *
     * @return self
     */
    public function references(
        string $table,
        array $columns = ['id'],
        bool $forcePrefix = true
    ): AbstractForeignKey {
        $this->foreignTable = ($forcePrefix ? $this->tablePrefix : '') . $table;
        $this->foreignKeys = $columns;

        return $this;
    }

    /**
     * Set foreign key delete behaviour.
     *
     * @param string $rule Possible values: NO ACTION, CASCADE, etc (driver specific).
     * @return self
     */
    public function onDelete(string $rule = self::NO_ACTION): AbstractForeignKey
    {
        $this->deleteRule = strtoupper($rule);

        return $this;
    }

    /**
     * Set foreign key update behaviour.
     *
     * @param string $rule Possible values: NO ACTION, CASCADE, etc (driver specific).
     * @return self
     */
    public function onUpdate(string $rule = self::NO_ACTION): AbstractForeignKey
    {
        $this->updateRule = strtoupper($rule);

        return $this;
    }

    /**
     * Foreign key creation syntax.
     *
     * @param DriverInterface $driver
     * @return string
     */
    public function sqlStatement(SpiralDriverInterface $driver): string
    {
        $statement = [];

        $statement[] = 'CONSTRAINT';
        $statement[] = $driver->identifier($this->name);
        $statement[] = 'FOREIGN KEY';
        $statement[] = '(' . $this->packColumns($driver, $this->columns) . ')';

        $statement[] = 'REFERENCES ' . $driver->identifier($this->foreignTable);
        $statement[] = '(' . $this->packColumns($driver, $this->foreignKeys) . ')';

        $statement[] = "ON DELETE {$this->deleteRule}";
        $statement[] = "ON UPDATE {$this->updateRule}";

        return implode(' ', $statement);
    }

    /**
     * @param AbstractForeignKey $initial
     * @return bool
     */
    public function compare(SpiralAbstractForeignKey $initial): bool
    {
        // soft compare
        return $this == clone $initial;
    }

    /**
     * @param DriverInterface $driver
     * @param array           $columns
     * @return string
     */
    protected function packColumns(SpiralDriverInterface $driver, array $columns): string
    {
        return implode(', ', array_map([$driver, 'identifier'], $columns));
    }
}
\class_alias(AbstractForeignKey::class, SpiralAbstractForeignKey::class, false);
