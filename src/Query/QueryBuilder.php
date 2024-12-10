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
 * Initiates active queries.
 */
final class QueryBuilder implements BuilderInterface
{
    private DriverInterface $driver;

    public function __construct(
        private SelectQuery $selectQuery,
        private InsertQuery $insertQuery,
        private UpdateQuery $updateQuery,
        private DeleteQuery $deleteQuery,
    ) {}

    public static function defaultBuilder(): self
    {
        return new self(
            new SelectQuery(),
            new InsertQuery(),
            new UpdateQuery(),
            new DeleteQuery(),
        );
    }

    public function withDriver(DriverInterface $driver): BuilderInterface
    {
        $builder = clone $this;
        $builder->driver = $driver;

        return $builder;
    }

    /**
     * Get InsertQuery builder with driver specific query compiler.
     */
    public function insertQuery(
        string $prefix,
        ?string $table = null,
    ): InsertQuery {
        $insert = $this->insertQuery->withDriver($this->driver, $prefix);

        if ($table !== null) {
            $insert->into($table);
        }

        return $insert;
    }

    /**
     * Get SelectQuery builder with driver specific query compiler.
     */
    public function selectQuery(
        string $prefix,
        array $from = [],
        array $columns = [],
    ): SelectQuery {
        $select = $this->selectQuery->withDriver($this->driver, $prefix);

        if ($columns === []) {
            $columns = ['*'];
        }

        return $select->from($from)->columns($columns);
    }

    public function deleteQuery(
        string $prefix,
        ?string $from = null,
        array $where = [],
    ): DeleteQuery {
        $delete = $this->deleteQuery->withDriver($this->driver, $prefix);

        if ($from !== null) {
            $delete->from($from);
        }

        return $delete->where($where);
    }

    /**
     * Get UpdateQuery builder with driver specific query compiler.
     */
    public function updateQuery(
        string $prefix,
        ?string $table = null,
        array $where = [],
        array $values = [],
    ): UpdateQuery {
        $update = $this->updateQuery->withDriver($this->driver, $prefix);

        if ($table !== null) {
            $update->in($table);
        }

        return $update->where($where)->values($values);
    }
}
