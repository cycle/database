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
use Spiral\Database\Query\SelectQuery as SpiralSelectQuery;
use Spiral\Database\Query\InsertQuery as SpiralInsertQuery;
use Spiral\Database\Query\UpdateQuery as SpiralUpdateQuery;
use Spiral\Database\Query\DeleteQuery as SpiralDeleteQuery;

/**
 * Initiates active queries.
 */
final class QueryBuilder implements BuilderInterface
{
    /** @var SelectQuery */
    private $selectQuery;

    /** @var InsertQuery */
    private $insertQuery;

    /** @var UpdateQuery */
    private $updateQuery;

    /** @var DeleteQuery */
    private $deleteQuery;

    /** @var DriverInterface */
    private $driver;

    /**
     * QueryBuilder constructor.
     *
     * @param SpiralSelectQuery|SelectQuery $selectQuery The signature of this
     *        argument will be changed to {@see SelectQuery} in future release.
     * @param SpiralInsertQuery|InsertQuery $insertQuery The signature of this
     *        argument will be changed to {@see InsertQuery} in future release.
     * @param SpiralUpdateQuery|UpdateQuery $updateQuery The signature of this
     *        argument will be changed to {@see UpdateQuery} in future release.
     * @param SpiralDeleteQuery|DeleteQuery $deleteQuery The signature of this
     *        argument will be changed to {@see DeleteQuery} in future release.
     */
    public function __construct(
        SpiralSelectQuery $selectQuery,
        SpiralInsertQuery $insertQuery,
        SpiralUpdateQuery $updateQuery,
        SpiralDeleteQuery $deleteQuery
    ) {
        $this->selectQuery = $selectQuery;
        $this->insertQuery = $insertQuery;
        $this->updateQuery = $updateQuery;
        $this->deleteQuery = $deleteQuery;
    }

    /**
     * @param DriverInterface $driver
     * @return BuilderInterface
     */
    public function withDriver(DriverInterface $driver): BuilderInterface
    {
        $builder = clone $this;
        $builder->driver = $driver;

        return $builder;
    }

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
    ): InsertQuery {
        $insert = $this->insertQuery->withDriver($this->driver, $prefix);

        if ($table !== null) {
            $insert->into($table);
        }

        return $insert;
    }

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
    ): SelectQuery {
        $select = $this->selectQuery->withDriver($this->driver, $prefix);

        if ($columns === []) {
            $columns = ['*'];
        }

        return $select->from($from)->columns($columns);
    }

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
    ): DeleteQuery {
        $delete = $this->deleteQuery->withDriver($this->driver, $prefix);

        if ($from !== null) {
            $delete->from($from);
        }

        return $delete->where($where);
    }

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
    ): UpdateQuery {
        $update = $this->updateQuery->withDriver($this->driver, $prefix);

        if ($table !== null) {
            $update->in($table);
        }

        return $update->where($where)->values($values);
    }

    /**
     * @return QueryBuilder
     */
    public static function defaultBuilder(): QueryBuilder
    {
        return new self(
            new SelectQuery(),
            new InsertQuery(),
            new UpdateQuery(),
            new DeleteQuery()
        );
    }
}
