<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\DriverInterface;

/**
 * Responsible for query initiation in a context of specific driver.
 */
interface BuilderInterface
{
    /**
     * @param DriverInterface $driver
     * @return BuilderInterface|$this
     */
    public function withDriver(DriverInterface $driver): self;

    /**
     * Get InsertQuery builder with driver specific query compiler.
     *
     * @param string      $prefix
     * @param string|null $table
     * @return InsertQuery
     */
    public function insertQuery(
        string $prefix,
        string $table = null
    ): InsertQuery;

    /**
     * Get SelectQuery builder with driver specific query compiler.
     *
     * @param string $prefix
     * @param array  $from
     * @param array  $columns
     * @return SelectQuery
     */
    public function selectQuery(
        string $prefix,
        array $from = [],
        array $columns = []
    ): SelectQuery;

    /**
     * @param string      $prefix
     * @param string|null $from
     * @param array       $where
     * @return DeleteQuery
     */
    public function deleteQuery(
        string $prefix,
        string $from = null,
        array $where = []
    ): DeleteQuery;

    /**
     * Get UpdateQuery builder with driver specific query compiler.
     *
     * @param string      $prefix
     * @param string|null $table
     * @param array       $where
     * @param array       $values
     * @return UpdateQuery
     */
    public function updateQuery(
        string $prefix,
        string $table = null,
        array $where = [],
        array $values = []
    ): UpdateQuery;
}
