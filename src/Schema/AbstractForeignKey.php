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

/**
 * Abstract foreign schema with read (see ReferenceInterface) and write abilities. Must be
 * implemented by driver to support DBMS specific syntax and creation rules.
 */
abstract class AbstractForeignKey implements ForeignKeyInterface, ElementInterface
{
    use ElementTrait;

    protected const EXCLUDE_FROM_COMPARE = ['index'];

    /**
     * Local column name (key name).
     */
    protected array $columns = [];

    /**
     * Referenced table name (including prefix).
     */
    protected string $foreignTable = '';

    /**
     * Linked foreign key name (foreign column).
     */
    protected array $foreignKeys = [];

    /**
     * Action on foreign column value deletion.
     */
    protected string $deleteRule = self::NO_ACTION;

    /**
     * Action on foreign column value update.
     */
    protected string $updateRule = self::NO_ACTION;

    /**
     * Create an index or not.
     */
    protected bool $index = true;

    /**
     * @psalm-param non-empty-string $table
     *
     * @psalm-param non-empty-string $name
     */
    public function __construct(
        protected string $table,
        protected string $tablePrefix,
        protected string $name,
    ) {}

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getForeignTable(): string
    {
        return $this->foreignTable;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getDeleteRule(): string
    {
        return $this->deleteRule;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getUpdateRule(): string
    {
        return $this->updateRule;
    }

    /**
     * Set local column names foreign key relates to. Make sure column type is the same as foreign
     * column one.
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Set foreign table name and key local column must reference to. Make sure local and foreign
     * column types are identical.
     *
     * @@psalm-param non-empty-string $table Foreign table name with or without database prefix (see 3rd argument).
     *
     * @param array $columns Foreign key names (id by default).
     * @param bool $forcePrefix When true foreign table will get same prefix as table being modified.
     */
    public function references(
        string $table,
        array $columns = ['id'],
        bool $forcePrefix = true,
    ): self {
        $this->foreignTable = ($forcePrefix ? $this->tablePrefix : '') . $table;
        $this->foreignKeys = $columns;

        return $this;
    }

    /**
     * Set foreign key delete behaviour.
     *
     * @psalm-param non-empty-string $rule Possible values: NO ACTION, CASCADE, etc (driver specific).
     */
    public function onDelete(string $rule = self::NO_ACTION): self
    {
        $this->deleteRule = \strtoupper($rule);

        return $this;
    }

    /**
     * Set foreign key update behaviour.
     *
     * @psalm-param non-empty-string $rule Possible values: NO ACTION, CASCADE, etc (driver specific).
     */
    public function onUpdate(string $rule = self::NO_ACTION): self
    {
        $this->updateRule = \strtoupper($rule);

        return $this;
    }

    public function setIndex(bool $index = true): static
    {
        $this->index = $index;

        return $this;
    }

    public function hasIndex(): bool
    {
        return $this->index;
    }

    /**
     * Foreign key creation syntax.
     *
     * @psalm-return non-empty-string
     */
    public function sqlStatement(DriverInterface $driver): string
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

        return \implode(' ', $statement);
    }

    public function compare(self $initial): bool
    {
        foreach ($this as $name => $value) {
            if (\in_array($name, static::EXCLUDE_FROM_COMPARE, true)) {
                continue;
            }
            if ($value !== $initial->$name) {
                return false;
            }
        }
        return true;
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function packColumns(DriverInterface $driver, array $columns): string
    {
        return \implode(', ', \array_map([$driver, 'identifier'], $columns));
    }
}
