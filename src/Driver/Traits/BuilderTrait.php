<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\Traits;

use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\CompilerInterface;
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
     * @param string      $prefix Database specific table prefix, used to quote table names and
     *                            build aliases.
     * @param string|null $table
     * @return InsertQuery
     */
    public function insertQuery(string $prefix, string $table = null): InsertQuery
    {
        return (new InsertQuery($table))->withDriver($this, $this->getCompiler($prefix));
    }

    /**
     * Get SelectQuery builder with driver specific query compiler.
     *
     * @param string $prefix Database specific table prefix, used to quote table names and build
     *                       aliases.
     * @param array  $from
     * @param array  $columns
     * @return SelectQuery
     */
    public function selectQuery(string $prefix, array $from = [], array $columns = []): SelectQuery
    {
        return (new SelectQuery($from, $columns))->withDriver($this, $this->getCompiler($prefix));
    }

    /**
     * @param string      $prefix
     * @param string|null $from  Database specific table prefix, used to quote table names and
     *                           build aliases.
     * @param array       $where Initial builder parameters.
     * @return DeleteQuery
     */
    public function deleteQuery(string $prefix, string $from = null, array $where = []): DeleteQuery
    {
        return (new DeleteQuery($from, $where))->withDriver($this, $this->getCompiler($prefix));
    }

    /**
     * Get UpdateQuery builder with driver specific query compiler.
     *
     * @param string      $prefix Database specific table prefix, used to quote table names and
     *                            build aliases.
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
        return (new UpdateQuery($table, $where, $values))->withDriver($this, $this->getCompiler($prefix));
    }

    /**
     * Get instance of Driver specific QueryCompiler.
     *
     * @param string $prefix Database specific table prefix, used to quote table names and build
     *                       aliases.
     *
     * @return Compiler
     */
    abstract public function getCompiler(string $prefix = ''): CompilerInterface;
}
