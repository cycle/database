<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;
use Spiral\Database\Schema\AbstractColumn as SpiralAbstractColumn;
use Spiral\Database\Schema\AbstractIndex as SpiralAbstractIndex;
use Spiral\Database\Schema\AbstractForeignKey as SpiralAbstractForeignKey;
use Spiral\Database\Schema\AbstractTable as SpiralAbstractTable;
use Spiral\Database\Driver\DriverInterface as SpiralDriverInterface;
use Spiral\Database\Driver\HandlerInterface as SpiralHandlerInterface;

interface_exists(SpiralDriverInterface::class);
class_exists(SpiralAbstractColumn::class);
class_exists(SpiralAbstractIndex::class);
class_exists(SpiralAbstractForeignKey::class);
class_exists(SpiralAbstractTable::class);

/**
 * Manages database schema.
 */
interface HandlerInterface
{
    //Foreign key modification behaviours
    public const DROP_FOREIGN_KEYS   = 0b000000001;
    public const CREATE_FOREIGN_KEYS = 0b000000010;
    public const ALTER_FOREIGN_KEYS  = 0b000000100;

    //All foreign keys related operations
    public const DO_FOREIGN_KEYS = self::DROP_FOREIGN_KEYS | self::ALTER_FOREIGN_KEYS | self::CREATE_FOREIGN_KEYS;

    //Column modification behaviours
    public const DROP_COLUMNS   = 0b000001000;
    public const CREATE_COLUMNS = 0b000010000;
    public const ALTER_COLUMNS  = 0b000100000;

    //All columns related operations
    public const DO_COLUMNS = self::DROP_COLUMNS | self::ALTER_COLUMNS | self::CREATE_COLUMNS;

    //Index modification behaviours
    public const DROP_INDEXES   = 0b001000000;
    public const CREATE_INDEXES = 0b010000000;
    public const ALTER_INDEXES  = 0b100000000;

    //All index related operations
    public const DO_INDEXES = self::DROP_INDEXES | self::ALTER_INDEXES | self::CREATE_INDEXES;

    //General purpose schema operations
    public const DO_RENAME = 0b10000000000;
    public const DO_DROP   = 0b01000000000;

    //All operations
    public const DO_ALL = self::DO_FOREIGN_KEYS | self::DO_INDEXES | self::DO_COLUMNS | self::DO_DROP | self::DO_RENAME;

    /**
     * @param DriverInterface $driver
     * @return HandlerInterface
     */
    public function withDriver(SpiralDriverInterface $driver): HandlerInterface;

    /**
     * Get all available table names.
     *
     * @return array
     */
    public function getTableNames(): array;

    /**
     * Check if given table exists in database.
     *
     * @param string $table
     * @return bool
     */
    public function hasTable(string $table): bool;

    /**
     * Get or create table schema.
     *
     * @param string      $table
     * @param string|null $prefix
     * @return AbstractTable
     *
     * @throws HandlerException
     */
    public function getSchema(string $table, string $prefix = null): AbstractTable;

    /**
     * Create table based on a given schema.
     *
     * @param AbstractTable $table
     * @throws HandlerException
     */
    public function createTable(SpiralAbstractTable $table): void;

    /**
     * Truncate table.
     *
     * @param AbstractTable $table
     * @throws HandlerException
     */
    public function eraseTable(SpiralAbstractTable $table): void;

    /**
     * Drop table from database.
     *
     * @param AbstractTable $table
     * @throws HandlerException
     */
    public function dropTable(SpiralAbstractTable $table): void;

    /**
     * Sync given table schema.
     *
     * @param AbstractTable $table
     * @param int                               $operation See behaviour constants.
     */
    public function syncTable(SpiralAbstractTable $table, int $operation = self::DO_ALL): void;

    /**
     * Rename table from one name to another.
     *
     * @param string $table
     * @param string $name
     *
     * @throws HandlerException
     */
    public function renameTable(string $table, string $name): void;

    /**
     * Driver specific column add command.
     *
     * @param AbstractTable  $table
     * @param AbstractColumn $column
     *
     * @throws HandlerException
     */
    public function createColumn(SpiralAbstractTable $table, SpiralAbstractColumn $column): void;

    /**
     * Driver specific column remove (drop) command.
     *
     * @param AbstractTable  $table
     * @param AbstractColumn $column
     */
    public function dropColumn(SpiralAbstractTable $table, SpiralAbstractColumn $column): void;

    /**
     * Driver specific column alter command.
     *
     * @param AbstractTable  $table
     * @param AbstractColumn $initial
     * @param AbstractColumn $column
     *
     * @throws HandlerException
     */
    public function alterColumn(
        SpiralAbstractTable $table,
        SpiralAbstractColumn $initial,
        SpiralAbstractColumn $column
    ): void;

    /**
     * Driver specific index adding command.
     *
     * @param AbstractTable $table
     * @param AbstractIndex $index
     *
     * @throws HandlerException
     */
    public function createIndex(SpiralAbstractTable $table, SpiralAbstractIndex $index): void;

    /**
     * Driver specific index remove (drop) command.
     *
     * @param AbstractTable $table
     * @param AbstractIndex $index
     *
     * @throws HandlerException
     */
    public function dropIndex(SpiralAbstractTable $table, SpiralAbstractIndex $index): void;

    /**
     * Driver specific index alter command, by default it will remove and add index.
     *
     * @param AbstractTable $table
     * @param AbstractIndex $initial
     * @param AbstractIndex $index
     *
     * @throws HandlerException
     */
    public function alterIndex(
        SpiralAbstractTable $table,
        SpiralAbstractIndex $initial,
        SpiralAbstractIndex $index
    ): void;

    /**
     * Driver specific foreign key adding command.
     *
     * @param AbstractTable      $table
     * @param AbstractForeignKey $foreignKey
     *
     * @throws HandlerException
     */
    public function createForeignKey(SpiralAbstractTable $table, SpiralAbstractForeignKey $foreignKey): void;

    /**
     * Driver specific foreign key remove (drop) command.
     *
     * @param AbstractTable      $table
     * @param AbstractForeignKey $foreignKey
     *
     * @throws HandlerException
     */
    public function dropForeignKey(SpiralAbstractTable $table, SpiralAbstractForeignKey $foreignKey): void;

    /**
     * Driver specific foreign key alter command, by default it will remove and add foreign key.
     *
     * @param AbstractTable      $table
     * @param AbstractForeignKey $initial
     * @param AbstractForeignKey $foreignKey
     *
     * @throws HandlerException
     */
    public function alterForeignKey(
        SpiralAbstractTable $table,
        SpiralAbstractForeignKey $initial,
        SpiralAbstractForeignKey $foreignKey
    ): void;

    /**
     * Drop column constraint using it's name.
     *
     * @param AbstractTable $table
     * @param string        $constraint
     *
     * @throws HandlerException
     */
    public function dropConstrain(SpiralAbstractTable $table, string $constraint): void;
}
\class_alias(HandlerInterface::class, SpiralHandlerInterface::class, false);
