<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;

final class ReadonlyHandler implements HandlerInterface
{
    public function __construct(
        private HandlerInterface $parent,
    ) {}

    public function withDriver(DriverInterface $driver): HandlerInterface
    {
        $handler = clone $this;
        $handler->parent = $handler->parent->withDriver($driver);

        return $handler;
    }

    public function getTableNames(string $prefix = ''): array
    {
        return $this->parent->getTableNames();
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public function hasTable(string $table): bool
    {
        return $this->parent->hasTable($table);
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public function getSchema(string $table, ?string $prefix = null): AbstractTable
    {
        return $this->parent->getSchema($table, $prefix);
    }

    public function createTable(AbstractTable $table): void {}

    public function eraseTable(AbstractTable $table): void
    {
        $this->parent->eraseTable($table);
    }

    public function dropTable(AbstractTable $table): void {}

    public function syncTable(AbstractTable $table, int $operation = self::DO_ALL): void {}

    /**
     * @psalm-param non-empty-string $table
     * @psalm-param non-empty-string $name
     */
    public function renameTable(string $table, string $name): void {}

    public function createColumn(AbstractTable $table, AbstractColumn $column): void {}

    public function dropColumn(AbstractTable $table, AbstractColumn $column): void {}

    public function alterColumn(AbstractTable $table, AbstractColumn $initial, AbstractColumn $column): void {}

    public function createIndex(AbstractTable $table, AbstractIndex $index): void {}

    public function dropIndex(AbstractTable $table, AbstractIndex $index): void {}

    public function alterIndex(AbstractTable $table, AbstractIndex $initial, AbstractIndex $index): void {}

    public function createForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void {}

    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void {}

    public function alterForeignKey(
        AbstractTable $table,
        AbstractForeignKey $initial,
        AbstractForeignKey $foreignKey,
    ): void {}

    /**
     * @psalm-param non-empty-string $constraint
     */
    public function dropConstrain(AbstractTable $table, string $constraint): void {}
}
