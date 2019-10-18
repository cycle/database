<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Driver\QueryBindings;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Injection\Parameter;

/**
 * Insert statement query builder, support singular and batch inserts.
 */
class InsertQuery extends AbstractQuery
{
    public const QUERY_TYPE = Compiler::INSERT_QUERY;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * Column names associated with insert.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Rowsets to be inserted.
     *
     * @var array
     */
    protected $rowsets = [];

    /**
     * @param string|null $table
     */
    public function __construct(string $table = null)
    {
        $this->table = $table ?? '';
    }

    /**
     * Set target insertion table.
     *
     * @param string $into
     * @return self
     */
    public function into(string $into): InsertQuery
    {
        $this->table = $into;

        return $this;
    }

    /**
     * Set insertion column names. Names can be provided as array, set of parameters or comma
     * separated string.
     *
     * Examples:
     * $insert->columns(["name", "email"]);
     * $insert->columns("name", "email");
     * $insert->columns("name, email");
     *
     * @param array|string $columns
     * @return self
     */
    public function columns(...$columns): InsertQuery
    {
        $this->columns = $this->fetchIdentifiers($columns);

        return $this;
    }

    /**
     * Set insertion rowset values or multiple rowsets. Values can be provided in multiple forms
     * (method parameters, array of values, array or rowsets). Columns names will be automatically
     * fetched (if not already specified) from first provided rowset based on rowset keys.
     *
     * Examples:
     * $insert->columns("name", "balance")->values("Wolfy-J", 10);
     * $insert->values([
     *      "name" => "Wolfy-J",
     *      "balance" => 10
     * ]);
     * $insert->values([
     *  [
     *      "name" => "Wolfy-J",
     *      "balance" => 10
     *  ],
     *  [
     *      "name" => "Ben",
     *      "balance" => 20
     *  ]
     * ]);
     *
     * @param mixed $rowsets
     * @return self
     */
    public function values($rowsets): InsertQuery
    {
        if (!is_array($rowsets)) {
            return $this->values(func_get_args());
        }

        if ($rowsets === []) {
            throw new BuilderException('Insert rowsets must not be empty');
        }

        //Checking if provided set is array of multiple
        reset($rowsets);

        if (!is_array($rowsets[key($rowsets)])) {
            if (empty($this->columns)) {
                $this->columns = array_keys($rowsets);
            }

            $this->rowsets[] = new Parameter(array_values($rowsets));
        } else {
            foreach ($rowsets as $rowset) {
                $this->rowsets[] = new Parameter(array_values($rowset));
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(QueryBindings $bindings, CompilerInterface $compiler): string
    {
        return $compiler->compileInsert($bindings, $this->table, $this->columns, $this->rowsets);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if ($this->compiler === null) {
            throw new BuilderException('Unable to run query without assigned driver');
        }

        $bindings = new QueryBindings();
        $queryString = $this->compile($bindings, $this->compiler);

        $this->driver->execute($queryString, $bindings->getParameters());

        return $this->driver->lastInsertID();
    }

    /**
     * Reset all insertion rowsets to make builder reusable (columns still set).
     */
    public function flushValues(): void
    {
        $this->rowsets = [];
    }
}
