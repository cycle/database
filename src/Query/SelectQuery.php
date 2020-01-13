<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Countable;
use IteratorAggregate;
use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Query\Traits\HavingTrait;
use Spiral\Database\Query\Traits\JoinTrait;
use Spiral\Database\Query\Traits\TokenTrait;
use Spiral\Database\Query\Traits\WhereTrait;
use Spiral\Database\StatementInterface;
use Spiral\Pagination\PaginableInterface;
use Throwable;

/**
 * Builds select sql statements.
 */
class SelectQuery extends ActiveQuery implements
    Countable,
    IteratorAggregate,
    PaginableInterface
{
    use TokenTrait;
    use WhereTrait;
    use HavingTrait;
    use JoinTrait;

    // sort directions
    public const SORT_ASC  = 'ASC';
    public const SORT_DESC = 'DESC';

    /** @var array */
    protected $tables = [];

    /** @var array */
    protected $unionTokens = [];

    /** @var bool|string */
    protected $distinct = false;

    /** @var array */
    protected $columns = ['*'];

    /** @var array */
    protected $orderBy = [];

    /** @var array */
    protected $groupBy = [];

    /** @var bool */
    protected $forUpdate = false;

    /** @var int */
    private $limit;

    /** @var int */
    private $offset;

    /**
     * @param array $from    Initial set of table names.
     * @param array $columns Initial set of columns to fetch.
     */
    public function __construct(array $from = [], array $columns = [])
    {
        $this->tables = $from;
        if ($columns !== []) {
            $this->columns = $this->fetchIdentifiers($columns);
        }
    }

    /**
     * Mark query to return only distinct results.
     *
     * @param bool|string|FragmentInterface $distinct You are only allowed to use string value for
     *                                                Postgres databases.
     * @return self|$this
     */
    public function distinct($distinct = true): SelectQuery
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Set table names SELECT query should be performed for. Table names can be provided with
     * specified alias (AS construction).
     *
     * @param array|string|mixed $tables
     * @return self|$this
     */
    public function from($tables): SelectQuery
    {
        $this->tables = $this->fetchIdentifiers(func_get_args());

        return $this;
    }

    /**
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
     * @param array|string|mixed $columns
     * @return self|$this
     */
    public function columns($columns): SelectQuery
    {
        $this->columns = $this->fetchIdentifiers(func_get_args());

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Select entities for the following update.
     *
     * @return self|$this
     */
    public function forUpdate(): SelectQuery
    {
        $this->forUpdate = true;

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
    public function orderBy($expression, $direction = self::SORT_ASC): SelectQuery
    {
        if (!is_array($expression)) {
            $this->orderBy[] = [$expression, $direction];

            return $this;
        }

        foreach ($expression as $nested => $dir) {
            $this->orderBy[] = [$nested, $dir];
        }

        return $this;
    }

    /**
     * Column or expression to group query by.
     *
     * @param string $expression
     * @return self|$this
     */
    public function groupBy($expression): SelectQuery
    {
        $this->groupBy[] = $expression;

        return $this;
    }

    /**
     * Add select query to be united with.
     *
     * @param FragmentInterface $query
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
     * Set selection limit. Attention, this limit value does not affect values set in paginator but
     * only changes pagination window. Set to 0 to disable limiting.
     *
     * @param int|null $limit
     * @return self|$this
     */
    public function limit(int $limit = null): SelectQuery
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Set selection offset. Attention, this value does not affect associated paginator but only
     * changes pagination window.
     *
     * @param int|null $offset
     * @return self|$this
     */
    public function offset(int $offset = null): SelectQuery
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * {@inheritdoc}
     *
     * @return StatementInterface
     */
    public function run(): StatementInterface
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        return $this->driver->query($queryString, $params->getParameters());
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
     *
     * @throws Throwable
     */
    public function runChunks(int $limit, callable $callback): void
    {
        $count = $this->count();

        // to keep original query untouched
        $select = clone $this;
        $select->limit($limit);

        $offset = 0;
        while ($offset + $limit <= $count) {
            $result = $callback(
                $select->offset($offset)->getIterator(),
                $offset,
                $count
            );

            // stop iteration
            if ($result === false) {
                return;
            }

            $offset += $limit;
        }
    }

    /**
     * Count number of rows in query. Limit, offset, order by, group by values will be ignored.
     *
     * @param string $column Column to count by (every column by default).
     * @return int
     */
    public function count(string $column = '*'): int
    {
        $select = clone $this;

        //To be escaped in compiler
        $select->columns = ["COUNT({$column})"];
        $select->orderBy = [];
        $select->groupBy = [];

        $st = $select->run();
        try {
            return (int)$st->fetchColumn();
        } finally {
            $st->close();
        }
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function avg(string $column)
    {
        return $this->runAggregate('AVG', $column);
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function max(string $column)
    {
        return $this->runAggregate('MAX', $column);
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function min(string $column)
    {
        return $this->runAggregate('MIN', $column);
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function sum(string $column)
    {
        return $this->runAggregate('SUM', $column);
    }

    /**
     * {@inheritdoc}
     *
     * @return StatementInterface
     */
    public function getIterator(): StatementInterface
    {
        return $this->run();
    }

    /**
     * Request all results as array.
     *
     * @return array
     */
    public function fetchAll(): array
    {
        $st = $this->run();
        try {
            return $st->fetchAll();
        } finally {
            $st->close();
        }
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return CompilerInterface::SELECT_QUERY;
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return [
            'forUpdate' => $this->forUpdate,
            'from'      => $this->tables,
            'join'      => $this->joinTokens,
            'columns'   => $this->columns,
            'distinct'  => $this->distinct,
            'where'     => $this->whereTokens,
            'having'    => $this->havingTokens,
            'groupBy'   => $this->groupBy,
            'orderBy'   => $this->orderBy,
            'limit'     => $this->limit,
            'offset'    => $this->offset,
            'union'     => $this->unionTokens,
        ];
    }

    /**
     * @param string $method
     * @param string $column
     * @return mixed
     */
    private function runAggregate(string $method, string $column)
    {
        $select = clone $this;

        //To be escaped in compiler
        $select->columns = ["{$method}({$column})"];

        $st = $select->run();
        try {
            return $st->fetchColumn();
        } finally {
            $st->close();
        }
    }
}
