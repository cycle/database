<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database;

/**
 * Represent table schema with it's all columns, indexes and foreign keys.
 */
interface TableInterface
{
    /**
     * Check if table exists in database.
     *
     */
    public function exists(): bool;

    /**
     * Store specific table name (with included prefix and without schema).
     *
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * Store specific table name (with included prefix and schema).
     *
     */
    public function getFullName(): string;

    /**
     * Array of columns dedicated to primary index. Attention, this methods will ALWAYS return
     * array, even if there is only one primary key.
     *
     * @return list<non-empty-string>
     */
    public function getPrimaryKeys(): array;

    /**
     * Check if table have specified column.
     *
     * @param string $name Column name.
     *
     */
    public function hasColumn(string $name): bool;

    /**
     * Get all declared columns.
     *
     * @return ColumnInterface[]
     */
    public function getColumns(): array;

    /**
     * Check if table has index related to set of provided columns. Columns order does matter!
     *
     */
    public function hasIndex(array $columns = []): bool;

    /**
     * Get all table indexes.
     *
     * @return IndexInterface[]
     */
    public function getIndexes(): array;

    /**
     * Check if table has foreign key related to table column.
     *
     * @param array $columns Column names.
     *
     */
    public function hasForeignKey(array $columns): bool;

    /**
     * Get all table foreign keys.
     *
     * @return ForeignKeyInterface[]
     */
    public function getForeignKeys(): array;

    /**
     * Get list of table names current schema depends on, must include every table linked using
     * foreign key or other constraint. Table names MUST include prefixes.
     *
     */
    public function getDependencies(): array;
}
