<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractTable;

final class ReadonlyHandler implements HandlerInterface
{
    /** @var HandlerInterface */
    private $parent;

    /**
     * @param HandlerInterface $parent
     */
    public function __construct(HandlerInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @inheritDoc
     */
    public function withDriver(DriverInterface $driver): HandlerInterface
    {
        $handler = clone $this;
        $handler->parent = $handler->parent->withDriver($driver);

        return $handler;
    }

    /**
     * @inheritDoc
     */
    public function getTableNames(): array
    {
        return $this->parent->getTableNames();
    }

    /**
     * @inheritDoc
     */
    public function hasTable(string $table): bool
    {
        return $this->parent->hasTable($table);
    }

    /**
     * @inheritDoc
     */
    public function getSchema(string $table, string $prefix = null): AbstractTable
    {
        return $this->parent->getSchema($table, $prefix);
    }

    /**
     * @inheritDoc
     */
    public function createTable(AbstractTable $table): void
    {
    }

    /**
     * @inheritDoc
     */
    public function eraseTable(AbstractTable $table): void
    {
        $this->parent->eraseTable($table);
    }

    /**
     * @inheritDoc
     */
    public function dropTable(AbstractTable $table): void
    {
    }

    /**
     * @inheritDoc
     */
    public function syncTable(AbstractTable $table, int $operation = self::DO_ALL): void
    {
    }

    /**
     * @inheritDoc
     */
    public function renameTable(string $table, string $name): void
    {
    }

    /**
     * @inheritDoc
     */
    public function createColumn(AbstractTable $table, AbstractColumn $column): void
    {
    }

    /**
     * @inheritDoc
     */
    public function dropColumn(AbstractTable $table, AbstractColumn $column): void
    {
    }

    /**
     * @inheritDoc
     */
    public function alterColumn(AbstractTable $table, AbstractColumn $initial, AbstractColumn $column): void
    {
    }

    /**
     * @inheritDoc
     */
    public function createIndex(AbstractTable $table, AbstractIndex $index): void
    {
    }

    /**
     * @inheritDoc
     */
    public function dropIndex(AbstractTable $table, AbstractIndex $index): void
    {
    }

    /**
     * @inheritDoc
     */
    public function alterIndex(AbstractTable $table, AbstractIndex $initial, AbstractIndex $index): void
    {
    }

    /**
     * @inheritDoc
     */
    public function createForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
    }

    /**
     * @inheritDoc
     */
    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void
    {
    }

    /**
     * @inheritDoc
     */
    public function alterForeignKey(
        AbstractTable $table,
        AbstractForeignKey $initial,
        AbstractForeignKey $foreignKey
    ): void {
    }

    /**
     * @inheritDoc
     */
    public function dropConstrain(AbstractTable $table, string $constraint): void
    {
    }
}
