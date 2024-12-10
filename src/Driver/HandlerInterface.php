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

/**
 * Manages database schema.
 *
 * @method void enableForeignKeyConstraints() Enable foreign key constraints. Will be added the next major release.
 * @method void disableForeignKeyConstraints() Disable foreign key constraints. Will be added the next major release.
 */
interface HandlerInterface
{
    //Foreign key modification behaviours
    public const DROP_FOREIGN_KEYS = 0b000000001;
    public const CREATE_FOREIGN_KEYS = 0b000000010;
    public const ALTER_FOREIGN_KEYS = 0b000000100;

    //All foreign keys related operations
    public const DO_FOREIGN_KEYS = self::DROP_FOREIGN_KEYS | self::ALTER_FOREIGN_KEYS | self::CREATE_FOREIGN_KEYS;

    //Column modification behaviours
    public const DROP_COLUMNS = 0b000001000;
    public const CREATE_COLUMNS = 0b000010000;
    public const ALTER_COLUMNS = 0b000100000;

    //All columns related operations
    public const DO_COLUMNS = self::DROP_COLUMNS | self::ALTER_COLUMNS | self::CREATE_COLUMNS;

    //Index modification behaviours
    public const DROP_INDEXES = 0b001000000;
    public const CREATE_INDEXES = 0b010000000;
    public const ALTER_INDEXES = 0b100000000;

    //All index related operations
    public const DO_INDEXES = self::DROP_INDEXES | self::ALTER_INDEXES | self::CREATE_INDEXES;

    //General purpose schema operations
    public const DO_RENAME = 0b10000000000;
    public const DO_DROP = 0b01000000000;

    //All operations
    public const DO_ALL = self::DO_FOREIGN_KEYS | self::DO_INDEXES | self::DO_COLUMNS | self::DO_DROP | self::DO_RENAME;

    public function withDriver(DriverInterface $driver): self;

    /**
     * Get all available table names.
     *
     * @param string|null $prefix
     *
     */
    public function getTableNames(string $prefix = ''): array;

    /**
     * Check if given table exists in database.
     *
     */
    public function hasTable(string $table): bool;

    /**
     * Get or create table schema.
     *
     * @throws HandlerException
     *
     */
    public function getSchema(string $table, ?string $prefix = null): AbstractTable;

    /**
     * Create table based on a given schema.
     *
     * @throws HandlerException
     */
    public function createTable(AbstractTable $table): void;

    /**
     * Truncate table.
     *
     * @throws HandlerException
     */
    public function eraseTable(AbstractTable $table): void;

    /**
     * Drop table from database.
     *
     * @throws HandlerException
     */
    public function dropTable(AbstractTable $table): void;

    /**
     * Sync given table schema.
     *
     * @param int           $operation See behaviour constants.
     */
    public function syncTable(AbstractTable $table, int $operation = self::DO_ALL): void;

    /**
     * Rename table from one name to another.
     *
     * @throws HandlerException
     */
    public function renameTable(string $table, string $name): void;

    /**
     * Driver specific column add command.
     *
     * @throws HandlerException
     */
    public function createColumn(AbstractTable $table, AbstractColumn $column): void;

    /**
     * Driver specific column remove (drop) command.
     *
     */
    public function dropColumn(AbstractTable $table, AbstractColumn $column): void;

    /**
     * Driver specific column alter command.
     *
     * @throws HandlerException
     */
    public function alterColumn(
        AbstractTable $table,
        AbstractColumn $initial,
        AbstractColumn $column,
    ): void;

    /**
     * Driver specific index adding command.
     *
     * @throws HandlerException
     */
    public function createIndex(AbstractTable $table, AbstractIndex $index): void;

    /**
     * Driver specific index remove (drop) command.
     *
     * @throws HandlerException
     */
    public function dropIndex(AbstractTable $table, AbstractIndex $index): void;

    /**
     * Driver specific index alter command, by default it will remove and add index.
     *
     * @throws HandlerException
     */
    public function alterIndex(
        AbstractTable $table,
        AbstractIndex $initial,
        AbstractIndex $index,
    ): void;

    /**
     * Driver specific foreign key adding command.
     *
     * @throws HandlerException
     */
    public function createForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void;

    /**
     * Driver specific foreign key remove (drop) command.
     *
     * @throws HandlerException
     */
    public function dropForeignKey(AbstractTable $table, AbstractForeignKey $foreignKey): void;

    /**
     * Driver specific foreign key alter command, by default it will remove and add foreign key.
     *
     * @throws HandlerException
     */
    public function alterForeignKey(
        AbstractTable $table,
        AbstractForeignKey $initial,
        AbstractForeignKey $foreignKey,
    ): void;

    /**
     * Drop column constraint using it's name.
     *
     * @throws HandlerException
     */
    public function dropConstrain(AbstractTable $table, string $constraint): void;
}
