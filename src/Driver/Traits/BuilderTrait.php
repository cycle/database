<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver\Traits;

use Spiral\Database\Driver\Compiler;
use Spiral\Database\Query\DeleteQuery;
use Spiral\Database\Query\InsertQuery;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\Query\UpdateQuery;

/**
 * Provides ability to construct query builders for the driver.
 */
trait BuilderTrait
{
    /**
     * Get InsertQuery builder with driver specific query compiler.
     *
     * @param string      $prefix Database specific table prefix, used to quote table names and build aliases.
     * @param string|null $table
     * @return InsertQuery
     */
    public function insertBuilder(string $prefix, string $table = null): InsertQuery
    {
        return new InsertQuery($this, $this->queryCompiler($prefix), $table);
    }

    /**
     * Get SelectQuery builder with driver specific query compiler.
     *
     * @param string $prefix Database specific table prefix, used to quote table names and build aliases.
     * @param array  $from
     * @param array  $columns
     * @return SelectQuery
     */
    public function selectBuilder(string $prefix, array $from = [], array $columns = []): SelectQuery
    {
        return new SelectQuery($this, $this->queryCompiler($prefix), $from, $columns);
    }

    /**
     * @param string      $prefix
     * @param string|null $from  Database specific table prefix, used to quote table names and build aliases.
     * @param array       $where Initial builder parameters.
     * @return DeleteQuery
     */
    public function deleteBuilder(string $prefix, string $from = null, array $where = []): DeleteQuery
    {
        return new DeleteQuery($this, $this->queryCompiler($prefix), $from, $where);
    }

    /**
     * Get UpdateQuery builder with driver specific query compiler.
     *
     * @param string      $prefix Database specific table prefix, used to quote table names and build aliases.
     * @param string|null $table
     * @param array       $where
     * @param array       $values
     * @return UpdateQuery
     */
    public function updateBuilder(
        string $prefix,
        string $table = null,
        array $where = [],
        array $values = []
    ): UpdateQuery {
        return new UpdateQuery($this, $this->queryCompiler($prefix), $table, $where, $values);
    }

    /**
     * Get instance of Driver specific QueryCompiler.
     *
     * @param string $prefix Database specific table prefix, used to quote table names and build
     *                       aliases.
     *
     * @return Compiler
     */
    abstract public function queryCompiler(string $prefix = ''): Compiler;
}