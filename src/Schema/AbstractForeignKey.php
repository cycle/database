<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Schema;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\ForeignKeyInterface;
use Spiral\Database\Schema\Traits\ElementTrait;

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
     * @var string
     */
    protected $column = '';

    /**
     * Referenced table name (including prefix).
     *
     * @var string
     */
    protected $foreignTable = '';

    /**
     * Linked foreign key name (foreign column).
     *
     * @var string
     */
    protected $foreignKey = '';

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
    public function getColumn(): string
    {
        return $this->column;
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
    public function getForeignKey(): string
    {
        return $this->foreignKey;
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
     * Set local column name foreign key relates to. Make sure column type is the same as foreign
     * column one.
     *
     * @param string $column
     * @return self
     */
    public function column(string $column): AbstractForeignKey
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Set foreign table name and key local column must reference to. Make sure local and foreign
     * column types are identical.
     *
     * @param string $table       Foreign table name with or without database prefix (see 3rd
     *                            argument).
     * @param string $column      Foreign key name (id by default).
     * @param bool   $forcePrefix When true foreign table will get same prefix as table being
     *                            modified.
     *
     * @return self
     */
    public function references(
        string $table,
        string $column = 'id',
        bool $forcePrefix = true
    ): AbstractForeignKey {
        $this->foreignTable = ($forcePrefix ? $this->tablePrefix : '') . $table;
        $this->foreignKey = $column;

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
    public function sqlStatement(DriverInterface $driver): string
    {
        $statement = [];

        $statement[] = 'CONSTRAINT';
        $statement[] = $driver->identifier($this->name);
        $statement[] = 'FOREIGN KEY';
        $statement[] = '(' . $driver->identifier($this->column) . ')';

        $statement[] = 'REFERENCES ' . $driver->identifier($this->foreignTable);
        $statement[] = '(' . $driver->identifier($this->foreignKey) . ')';

        $statement[] = "ON DELETE {$this->deleteRule}";
        $statement[] = "ON UPDATE {$this->updateRule}";

        return implode(' ', $statement);
    }

    /**
     * @param AbstractForeignKey $initial
     * @return bool
     */
    public function compare(AbstractForeignKey $initial): bool
    {
        return $this == clone $initial;
    }
}