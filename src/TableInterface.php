<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database;

/**
 * Represent table schema with it's all columns, indexes and foreign keys.
 */
interface TableInterface
{
    /**
     * Check if table exists in database.
     *
     * @return bool
     */
    public function exists(): bool;

    /**
     * Store specific table name (with included prefix).
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Array of columns dedicated to primary index. Attention, this methods will ALWAYS return
     * array, even if there is only one primary key.
     *
     * @return array
     */
    public function getPrimaryKeys(): array;

    /**
     * Check if table have specified column.
     *
     * @param string $name Column name.
     * @return bool
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
     * @param array $columns
     * @return bool
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
     * @return bool
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
     * @return array
     */
    public function getDependencies(): array;
}
