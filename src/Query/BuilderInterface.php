<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Driver\DriverInterface;
use Spiral\Database\Driver\DriverInterface as SpiralDriverInterface;
use Spiral\Database\Query\BuilderInterface as SpiralBuilderInterface;

interface_exists(SpiralDriverInterface::class);

/**
 * Responsible for query initiation in a context of specific driver.
 */
interface BuilderInterface
{
    /**
     * @param DriverInterface $driver
     *
     * @return $this|BuilderInterface
     */
    public function withDriver(SpiralDriverInterface $driver): self;

    /**
     * Get InsertQuery builder with driver specific query compiler.
     *
     * @param string      $prefix
     * @param string|null $table
     *
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
     *
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
     *
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
     *
     * @return UpdateQuery
     */
    public function updateQuery(
        string $prefix,
        string $table = null,
        array $where = [],
        array $values = []
    ): UpdateQuery;
}
\class_alias(BuilderInterface::class, SpiralBuilderInterface::class, false);
