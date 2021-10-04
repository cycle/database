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
 * Must implement Traversable as IteratorAggregate or Iterator. You can access underlying PDOStatement
 * using getPDOStatement() method of `Cycle\Database\Driver\Statement` object.
 */
interface StatementInterface extends \Traversable
{
    // Fetch rows as assoc array. Default.
    public const FETCH_ASSOC = 2;

    // Fetch rows as array where each key is column number.
    public const FETCH_NUM = 3;

    // Fetch rows as object where each property is the column name.
    public const FETCH_OBJ = 5;

    /**
     * @return string
     */
    public function getQueryString(): string;

    /**
     * Must return the next row of a result set.
     *
     * @param int $mode
     * @return mixed
     * @psalm-suppress MissingReturnType
     */
    public function fetch(int $mode = self::FETCH_ASSOC);

    /**
     * Must return a single column from the next row of a result set.
     *
     * @param int $columnNumber Optional column number.
     * @return mixed
     */
    public function fetchColumn(int $columnNumber = null);

    /**
     * Fetch all rows.
     *
     * @param int $mode Fetch mode.
     * @return array
     */
    public function fetchAll(int $mode = self::FETCH_ASSOC): array;

    /**
     * Number of rows in a statement.
     *
     * @return int
     */
    public function rowCount(): int;

    /**
     * Return number of columns in a statement.
     *
     * @return int
     */
    public function columnCount(): int;

    /**
     * Close the statement, must be called once all the data is retrieved.
     */
    public function close();
}
