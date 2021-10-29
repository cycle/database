<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Schema\AbstractTable;
use Spiral\Database\Driver\DriverInterface as SpiralDriverInterface;
use Spiral\Database\Driver\HandlerInterface as SpiralHandlerInterface;
use Spiral\Database\Schema\AbstractForeignKey as SpiralAbstractForeignKey;
use Spiral\Database\Schema\AbstractColumn as SpiralAbstractColumn;
use Spiral\Database\Schema\AbstractIndex as SpiralAbstractIndex;
use Spiral\Database\Schema\AbstractTable as SpiralAbstractTable;

interface_exists(SpiralDriverInterface::class);
interface_exists(SpiralHandlerInterface::class);
class_exists(SpiralAbstractForeignKey::class);
class_exists(SpiralAbstractColumn::class);
class_exists(SpiralAbstractIndex::class);
class_exists(SpiralAbstractTable::class);

final class ReadonlyHandler implements HandlerInterface
{
    /** @var HandlerInterface */
    private $parent;

    /**
     * @param HandlerInterface $parent
     */
    public function __construct(SpiralHandlerInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @inheritDoc
     */
    public function withDriver(SpiralDriverInterface $driver): HandlerInterface
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
    public function createTable(SpiralAbstractTable $table): void
    {
    }

    /**
     * @inheritDoc
     */
    public function eraseTable(SpiralAbstractTable $table): void
    {
        $this->parent->eraseTable($table);
    }

    /**
     * @inheritDoc
     */
    public function dropTable(SpiralAbstractTable $table): void
    {
    }

    /**
     * @inheritDoc
     */
    public function syncTable(SpiralAbstractTable $table, int $operation = self::DO_ALL): void
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
    public function createColumn(SpiralAbstractTable $table, SpiralAbstractColumn $column): void
    {
    }

    /**
     * @inheritDoc
     */
    public function dropColumn(SpiralAbstractTable $table, SpiralAbstractColumn $column): void
    {
    }

    /**
     * @inheritDoc
     */
    public function alterColumn(SpiralAbstractTable $table, SpiralAbstractColumn $initial, SpiralAbstractColumn $column): void
    {
    }

    /**
     * @inheritDoc
     */
    public function createIndex(SpiralAbstractTable $table, SpiralAbstractIndex $index): void
    {
    }

    /**
     * @inheritDoc
     */
    public function dropIndex(SpiralAbstractTable $table, SpiralAbstractIndex $index): void
    {
    }

    /**
     * @inheritDoc
     */
    public function alterIndex(SpiralAbstractTable $table, SpiralAbstractIndex $initial, SpiralAbstractIndex $index): void
    {
    }

    /**
     * @inheritDoc
     */
    public function createForeignKey(SpiralAbstractTable $table, SpiralAbstractForeignKey $foreignKey): void
    {
    }

    /**
     * @inheritDoc
     */
    public function dropForeignKey(SpiralAbstractTable $table, SpiralAbstractForeignKey $foreignKey): void
    {
    }

    /**
     * @inheritDoc
     */
    public function alterForeignKey(
        SpiralAbstractTable $table,
        SpiralAbstractForeignKey $initial,
        SpiralAbstractForeignKey $foreignKey
    ): void {
    }

    /**
     * @inheritDoc
     */
    public function dropConstrain(SpiralAbstractTable $table, string $constraint): void
    {
    }
}
