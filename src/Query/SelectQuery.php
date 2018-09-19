<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Query;

use Spiral\Database\Query\AbstractSelect;
use Spiral\Database\Driver;
use Spiral\Database\QueryCompiler;
use Spiral\Database\QueryStatement;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Exception\QueryException;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Debug\Traits\LoggerTrait;

/**
 * SelectQuery extends AbstractSelect with ability to specify selection tables and perform UNION
 * of multiple select queries.
 */
class SelectQuery extends AbstractSelect implements \JsonSerializable, \Countable
{
    //See SQL generation below
    use LoggerTrait;

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
     * {@inheritdoc}
     *
     * @param array $from    Initial set of table names.
     * @param array $columns Initial set of columns to fetch.
     */
    public function __construct(
        Driver $driver,
        QueryCompiler $compiler,
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
     * Set table names SELECT query should be performed for. Table names can be provided with
     * specified alias (AS construction).
     *
     * @param array|string|mixed $tables Array of names, comma separated string or set of
     *                                   parameters.
     *
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
     *
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
        $parameters = parent::getParameters();

        //Unions always located at the end of query.
        foreach ($this->joinTokens as $join) {
            if ($join['outer'] instanceof QueryBuilder) {
                $parameters = array_merge($parameters, $join['outer']->getParameters());
            }
        }

        //Unions always located at the end of query.
        foreach ($this->unionTokens as $union) {
            if ($union[1] instanceof QueryBuilder) {
                $parameters = array_merge($parameters, $union[1]->getParameters());
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $paginate Apply pagination to result, can be disabled in honor of count method.
     *
     * @return QueryStatement
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
     *
     * @return int
     */
    public function count(string $column = '*'): int
    {
        /**
         * @var AbstractSelect $select
         */
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
     *
     * @return int|float
     *
     * @throws BuilderException
     * @throws QueryException
     */
    public function __call($method, array $arguments)
    {
        if (!in_array($method = strtoupper($method), ['AVG', 'MIN', 'MAX', 'SUM'])) {
            throw new BuilderException("Unknown method '{$method}' in '" . get_class($this) . "'");
        }

        if (!isset($arguments[0]) || count($arguments) > 1) {
            throw new BuilderException('Aggregation methods can support exactly one column');
        }

        /**
         * @var AbstractSelect $select
         */
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
     * @return \PDOStatement|QueryStatement
     */
    public function getIterator()
    {
        return $this->run();
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(QueryCompiler $compiler = null): string
    {
        if (empty($compiler)) {
            $compiler = $this->compiler->resetQuoter();
        }

        if ((!empty($this->getLimit()) || !empty($this->getOffset())) && empty($this->ordering)) {
            $this->logger()->warning(
                "Usage of LIMIT/OFFSET without proper ORDER BY statement is ambiguous"
            );
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
