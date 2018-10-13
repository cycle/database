<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Query;

use Spiral\Database\Driver\AbstractDriver;
use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Query\Traits\HavingTrait;
use Spiral\Database\Query\Traits\JoinTrait;
use Spiral\Database\Query\Traits\TokenTrait;
use Spiral\Database\Query\Traits\WhereTrait;
use Spiral\Database\Statement;
use Spiral\Pagination\PaginatorAwareInterface;
use Spiral\Pagination\Traits\LimitsTrait;
use Spiral\Pagination\Traits\PaginatorTrait;

/**
 * SelectQuery extends AbstractSelect with ability to specify selection tables and perform UNION
 * of multiple select queries.
 */
class SelectQuery extends AbstractQuery implements
    \JsonSerializable,
    \Countable,
    \IteratorAggregate,
    PaginatorAwareInterface
{
    use TokenTrait, WhereTrait, HavingTrait, JoinTrait, LimitsTrait, PaginatorTrait;

    const QUERY_TYPE = Compiler::SELECT_QUERY;

    /**
     * Sort directions.
     */
    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    /**
     * Table names to select data from.
     *
     * @var array
     */
    protected $tables = [];

    /**
     * Select queries represented by sql fragments or query builders to be united. Stored as
     * [UNION TYPE, SELECT QUERY].
     *
     * @var array
     */
    protected $unionTokens = [];

    /**
     * Query must return only unique rows.
     *
     * @var bool|string
     */
    protected $distinct = false;

    /**
     * Columns or expressions to be fetched from database, can include aliases (AS).
     *
     * @var array
     */
    protected $columns = ['*'];

    /**
     * Columns/expression associated with their sort direction (ASK|DESC).
     *
     * @var array
     */
    protected $ordering = [];

    /**
     * Columns/expressions to group by.
     *
     * @var array
     */
    protected $grouping = [];

    /**
     * {@inheritdoc}
     *
     * @param array $from    Initial set of table names.
     * @param array $columns Initial set of columns to fetch.
     */
    public function __construct(
        AbstractDriver $driver,
        Compiler $compiler,
        array $from = [],
        array $columns = []
    ) {
        parent::__construct($driver, $compiler);

        $this->tables = $from;
        if (!empty($columns)) {
            $this->columns = $this->fetchIdentifiers($columns);
        }
    }

    /**
     * Mark query to return only distinct results.
     *
     * @param bool|string|FragmentInterface $distinct You are only allowed to use string value for
     *                                                Postgres databases.
     *
     * @return self|$this
     */
    public function distinct($distinct = true): self
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Sort result by column/expression. You can apply multiple sortings to query via calling method
     * few times or by specifying values using array of sort parameters.
     *
     * $select->orderBy([
     *      'id'   => SelectQuery::SORT_DESC,
     *      'name' => SelectQuery::SORT_ASC
     * ]);
     *
     * @param string|array $expression
     * @param string       $direction Sorting direction, ASC|DESC.
     * @return self|$this
     */
    public function orderBy($expression, $direction = self::SORT_ASC): self
    {
        if (!is_array($expression)) {
            $this->ordering[] = [$expression, $direction];

            return $this;
        }

        foreach ($expression as $nested => $direction) {
            $this->ordering[] = [$nested, $direction];
        }

        return $this;
    }

    /**
     * Column or expression to group query by.
     *
     * @param string $expression
     * @return self|$this
     */
    public function groupBy($expression): self
    {
        $this->grouping[] = $expression;

        return $this;
    }

    /**
     * Set table names SELECT query should be performed for. Table names can be provided with
     * specified alias (AS construction).
     *
     * @param array|string|mixed $tables Array of names, comma separated string or set of
     *                                   parameters.
     * @return self|$this
     */
    public function from($tables): SelectQuery
    {
        $this->tables = $this->fetchIdentifiers(func_get_args());

        return $this;
    }

    /**
     * Tables to be loaded.
     *
     * @return array
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Set columns should be fetched as result of SELECT query. Columns can be provided with
     * specified alias (AS construction).
     *
     * @param array|string|mixed $columns Array of names, comma separated string or set of
     *                                    parameters.
     * @return self|$this
     */
    public function columns($columns): SelectQuery
    {
        $this->columns = $this->fetchIdentifiers(func_get_args());

        return $this;
    }

    /**
     * Set of columns to be selected.
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Add select query to be united with.
     *
     * @param FragmentInterface $query
     *
     * @return self|$this
     */
    public function union(FragmentInterface $query): SelectQuery
    {
        $this->unionTokens[] = ['', $query];

        return $this;
    }

    /**
     * Add select query to be united with. Duplicate values will be included in result.
     *
     * @param FragmentInterface $query
     *
     * @return self|$this
     */
    public function unionAll(FragmentInterface $query): SelectQuery
    {
        $this->unionTokens[] = ['ALL', $query];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        $parameters = $this->flattenParameters(array_merge(
            $this->onParameters,
            $this->whereParameters,
            $this->havingParameters
        ));

        //Unions always located at the end of query.
        foreach ($this->joinTokens as $join) {
            if ($join['outer'] instanceof BuilderInterface) {
                $parameters = array_merge($parameters, $join['outer']->getParameters());
            }
        }

        //Unions always located at the end of query.
        foreach ($this->unionTokens as $union) {
            if ($union[1] instanceof BuilderInterface) {
                $parameters = array_merge($parameters, $union[1]->getParameters());
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $paginate Apply pagination to result, can be disabled in honor of count method.
     * @return Statement
     */
    public function run(bool $paginate = true)
    {
        if ($paginate && $this->hasPaginator()) {
            /**
             * To prevent original select builder altering
             *
             * @var SelectQuery $select
             */
            $select = clone $this;

            //Selection specific paginator
            $paginator = $this->getPaginator(true);

            if (!empty($this->getLimit()) && $this->getLimit() > $paginator->getLimit()) {
                //We have to ensure that selection works inside given pagination window
                $select = $select->limit($this->getLimit());
            } else {
                $select->limit($paginator->getLimit());
            }

            //Making sure that window is shifted
            $select = $select->offset($this->getOffset() + $paginator->getOffset());

            //No inner pagination
            return $select->run(false);
        }

        return $this->driver->query($this->sqlStatement(), $this->getParameters());
    }

    /**
     * Iterate thought result using smaller data chinks with defined size and walk function.
     *
     * Example:
     * $select->chunked(100, function(PDOResult $result, $offset, $count) {
     *      dump($result);
     * });
     *
     * You must return FALSE from walk function to stop chunking.
     *
     * @param int      $limit
     * @param callable $callback
     */
    public function runChunks(int $limit, callable $callback)
    {
        $count = $this->count();

        //To keep original query untouched
        $select = clone $this;
        $select->limit($limit);

        $offset = 0;
        while ($offset + $limit <= $count) {
            $result = call_user_func_array(
                $callback,
                [$select->offset($offset)->getIterator(), $offset, $count]
            );

            if ($result === false) {
                //Stop iteration
                return;
            }

            $offset += $limit;
        }
    }

    /**
     * {@inheritdoc}
     *
     * Count number of rows in query. Limit, offset, order by, group by values will be ignored. Do
     * not count united queries, or queries in complex joins.
     *
     * @param string $column Column to count by (every column by default).
     * @return int
     */
    public function count(string $column = '*'): int
    {
        $select = clone $this;

        //To be escaped in compiler
        $select->columns = ["COUNT({$column})"];
        $select->ordering = [];
        $select->grouping = [];

        return (int)$select->run(false)->fetchColumn();
    }

    /**
     * {@inheritdoc}
     *
     * Shortcut to execute one of aggregation methods (AVG, MAX, MIN, SUM) using method name as
     * reference.
     *
     * Example:
     * echo $select->sum('user.balance');
     *
     * @param string $method
     * @param array  $arguments
     * @return int|float
     *
     * @throws BuilderException
     * @throws StatementException
     */
    public function __call($method, array $arguments)
    {
        if (!in_array($method = strtoupper($method), ['AVG', 'MIN', 'MAX', 'SUM'])) {
            throw new BuilderException("Unknown method '{$method}' in '" . get_class($this) . "'");
        }

        if (!isset($arguments[0]) || count($arguments) > 1) {
            throw new BuilderException('Aggregation methods can support exactly one column');
        }

        $select = clone $this;

        //To be escaped in compiler
        $select->columns = ["{$method}({$arguments[0]})"];

        $result = $select->run(false)->fetchColumn();

        //Selecting type between int and float
        if ((float)$result == $result && (int)$result != $result) {
            //Find more elegant check
            return (float)$result;
        }

        return (int)$result;
    }

    /**
     * {@inheritdoc}
     *
     * @return \PDOStatement|Statement
     */
    public function getIterator()
    {
        return $this->run();
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(CompilerInterface $compiler = null): string
    {
        if (empty($compiler)) {
            $compiler = clone $this->compiler;
        }

        //11 parameters!
        return $compiler->compileSelect(
            $this->tables,
            $this->distinct,
            $this->columns,
            $this->joinTokens,
            $this->whereTokens,
            $this->havingTokens,
            $this->grouping,
            $this->ordering,
            $this->getLimit(),
            $this->getOffset(),
            $this->unionTokens
        );
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->fetchAll();
    }

    /**
     * Request all results as array.
     *
     * @return array
     */
    public function fetchAll(): array
    {
        return $this->getIterator()->fetchAll(\PDO::FETCH_ASSOC);
    }
}
