<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Exception\HandlerException;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractTable;

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
    public function withDriver(DriverInterface $driver): HandlerInterface;

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
    public function createTable(AbstractTable $table): void;

    /**
     * Truncate table.
     *
     * @param AbstractTable $table
     * @throws HandlerException
     */
    public function eraseTable(AbstractTable $table): void;

    /**
     * Drop table from database.
     *
     * @param AbstractTable $table
     * @throws HandlerException
     */
    public function dropTable(AbstractTable $table): void;

    /**
     * Sync given table schema.
     *
     * @param AbstractTable $table
     * @param int           $operation See behaviour constants.
     */
    public function syncTable(AbstractTable $table, int $operation = self::DO_ALL): void;

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
    public function createColumn(AbstractTable $table, AbstractColumn $column): void;

    /**
     * Driver specific column remove (drop) command.
     *
     * @param AbstractTable  $table
     * @param AbstractColumn $column
     */
    public function dropColumn(AbstractTable $table, AbstractColumn $column): void;

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
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column
    ): void;

    /**
     * Driver specific index adding command.
     *
     * @param AbstractTable $table
     * @param AbstractIndex $index
     *
     * @throws HandlerException
     */
    public function createIndex(AbstractTable $table, AbstractIndex $index): void;

    /**
     * Driver specific index remove (drop) command.
     *
     * @param AbstractTable $table
     * @param AbstractIndex $index
     *
     * @throws HandlerException
     */
    public function dropIndex(AbstractTable $table, AbstractIndex $index): void;

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
        AbstractTable $table,
        AbstractIndex $initial,
        AbstractIndex $index
    ): void;

    /**
     * Driver specific foreign key adding command.
     *
     * @param AbstractTable      $table
     * @param AbstractForeignKey $foreignKey
     *
     * @throws HandlerException
     */
    public function createForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void;

    /**
     * Driver specific foreign key remove (drop) command.
     *
     * @param AbstractTable      $table
     * @param AbstractForeignKey $foreignKey
     *
     * @throws HandlerException
     */
    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void;

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
        AbstractTable $table,
        AbstractForeignKey $initial,
        AbstractForeignKey $foreignKey
    ): void;

    /**
     * Drop column constraint using it's name.
     *
     * @param AbstractTable $table
     * @param string        $constraint
     *
     * @throws HandlerException
     */
    public function dropConstrain(AbstractTable $table, string $constraint): void;
}
