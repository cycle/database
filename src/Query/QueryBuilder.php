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
     * @param SelectQuery $selectQuery
     * @param InsertQuery $insertQuery
     * @param UpdateQuery $updateQuery
     * @param DeleteQuery $deleteQuery
     */
    public function __construct(
        SelectQuery $selectQuery,
        InsertQuery $insertQuery,
        UpdateQuery $updateQuery,
        DeleteQuery $deleteQuery
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
