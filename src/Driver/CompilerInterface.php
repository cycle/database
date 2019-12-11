<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Exception\CompilerException;
use Spiral\Database\Injection\FragmentInterface;

interface CompilerInterface
{
    /**
     * Prefix associated with compiler.
     *
     * @return string
     */
    public function getPrefix(): string;

    /**
     * Query query identifier, if identified stated as table - table prefix must be added.
     *
     * @param QueryBindings            $bindings
     * @param string|FragmentInterface $identifier Identifier can include simple column operations and functions, having
     *                                             "." in it must automatically force table prefix to first value.
     * @param bool                     $isTable    Set to true to let quote method know that identified is related to
     *                                             table name.
     * @return string
     */
    public function quote(QueryBindings $bindings, $identifier, bool $isTable = false): string;

    /**
     * Create insert query using table names, columns and rowsets. Must support both - single and
     * batch inserts.
     *
     * @param QueryBindings       $bindings
     * @param string              $table
     * @param array               $columns
     * @param FragmentInterface[] $values Every rowset has to be convertable into string. Raw data not allowed!
     * @return string
     *
     * @throws CompilerException
     */
    public function compileInsert(
        QueryBindings $bindings,
        string $table,
        array $columns,
        array $values
    ): string;

    /**
     * Create update statement.
     *
     * @param QueryBindings $bindings
     * @param string        $table
     * @param array         $updates
     * @param array         $whereTokens
     * @return string
     *
     * @throws CompilerException
     */
    public function compileUpdate(
        QueryBindings $bindings,
        string $table,
        array $updates,
        array $whereTokens = []
    ): string;

    /**
     * Create delete statement.
     *
     * @param QueryBindings $bindings
     * @param string        $table
     * @param array         $whereTokens
     * @return string
     *
     * @throws CompilerException
     */
    public function compileDelete(
        QueryBindings $bindings,
        string $table,
        array $whereTokens = []
    ): string;

    /**
     * Create select statement. Compiler must validly resolve table and column aliases used in
     * conditions and joins.
     *
     * @param QueryBindings $bindings
     * @param array         $fromTables
     * @param bool|string   $distinct String only for PostgresSQL.
     * @param array         $columns
     * @param array         $joinTokens
     * @param array         $whereTokens
     * @param array         $havingTokens
     * @param array         $grouping
     * @param array         $orderBy
     * @param int           $limit
     * @param int           $offset
     * @param array         $unionTokens
     * @param bool          $forUpdate
     * @return string
     *
     * @throws CompilerException
     */
    public function compileSelect(
        QueryBindings $bindings,
        array $fromTables,
        $distinct,
        array $columns,
        array $joinTokens = [],
        array $whereTokens = [],
        array $havingTokens = [],
        array $grouping = [],
        array $orderBy = [],
        int $limit = 0,
        int $offset = 0,
        array $unionTokens = [],
        bool $forUpdate = false
    ): string;
}
