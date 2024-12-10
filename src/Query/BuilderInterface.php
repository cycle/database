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

/**
 * Responsible for query initiation in a context of specific driver.
 */
interface BuilderInterface
{
    /**
     *
     * @return $this|BuilderInterface
     */
    public function withDriver(DriverInterface $driver): self;

    /**
     * Get InsertQuery builder with driver specific query compiler.
     *
     */
    public function insertQuery(
        string $prefix,
        ?string $table = null,
    ): InsertQuery;

    /**
     * Get SelectQuery builder with driver specific query compiler.
     *
     */
    public function selectQuery(
        string $prefix,
        array $from = [],
        array $columns = [],
    ): SelectQuery;

    public function deleteQuery(
        string $prefix,
        ?string $from = null,
        array $where = [],
    ): DeleteQuery;

    /**
     * Get UpdateQuery builder with driver specific query compiler.
     *
     */
    public function updateQuery(
        string $prefix,
        ?string $table = null,
        array $where = [],
        array $values = [],
    ): UpdateQuery;
}
