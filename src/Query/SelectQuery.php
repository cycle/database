<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Countable;
use IteratorAggregate;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\Traits\HavingTrait;
use Cycle\Database\Query\Traits\JoinTrait;
use Cycle\Database\Query\Traits\TokenTrait;
use Cycle\Database\Query\Traits\WhereTrait;
use Cycle\Database\StatementInterface;
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
    use HavingTrait;
    use JoinTrait;
    use TokenTrait;
    use WhereTrait;

    // sort directions
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    /** @var array */
    protected $tables = [];

    /** @var array */
    protected $unionTokens = [];

    /** @var bool|string */
    protected $distinct = false;

    /** @var array */
    protected $columns = ['*'];

    /** @var FragmentInterface[][]|string[][] */
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
     * @param bool|FragmentInterface|string $distinct You are only allowed to use string value for
     *                                                Postgres databases.
     *
     * @return $this|self
     */
    public function distinct($distinct = true): self
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Set table names SELECT query should be performed for. Table names can be provided with
     * specified alias (AS construction).
     *
     * @param array|mixed|string $tables
     *
     * @return $this|self
     */
    public function from($tables): self
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
     * @param array|mixed|string $columns
     *
     * @return $this|self
     */
    public function columns($columns): self
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
     * @return $this|self
     */
    public function forUpdate(): self
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
     * @param array|string $expression
     * @param string       $direction Sorting direction, ASC|DESC.
     *
     * @return $this|self
     */
    public function orderBy($expression, $direction = self::SORT_ASC): self
    {
        if (!is_array($expression)) {
            $this->addOrder($expression, $direction);
            return $this;
        }

        foreach ($expression as $nested => $dir) {
            $this->addOrder($nested, $dir);
        }

        return $this;
    }

    /**
     * Column or expression to group query by.
     *
     * @param string $expression
     *
     * @return $this|self
     */
    public function groupBy($expression): self
    {
        $this->groupBy[] = $expression;

        return $this;
    }

    /**
     * Add select query to be united with.
     *
     * @param FragmentInterface $query
     *
     * @return $this|self
     */
    public function union(FragmentInterface $query): self
    {
        $this->unionTokens[] = ['', $query];

        return $this;
    }

    /**
     * Add select query to be united with. Duplicate values will be included in result.
     *
     * @param FragmentInterface $query
     *
     * @return $this|self
     */
    public function unionAll(FragmentInterface $query): self
    {
        $this->unionTokens[] = ['ALL', $query];

        return $this;
    }

    /**
     * Set selection limit. Attention, this limit value does not affect values set in paginator but
     * only changes pagination window. Set to 0 to disable limiting.
     *
     * @param int|null $limit
     *
     * @return $this|self
     */
    public function limit(int $limit = null): self
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
     *
     * @return $this|self
     */
    public function offset(int $offset = null): self
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
     *
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
     *
     * @return mixed
     */
    public function avg(string $column)
    {
        return $this->runAggregate('AVG', $column);
    }

    /**
     * @param string $column
     *
     * @return mixed
     */
    public function max(string $column)
    {
        return $this->runAggregate('MAX', $column);
    }

    /**
     * @param string $column
     *
     * @return mixed
     */
    public function min(string $column)
    {
        return $this->runAggregate('MIN', $column);
    }

    /**
     * @param string $column
     *
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
    public function fetchAll(int $mode = StatementInterface::FETCH_ASSOC): array
    {
        $st = $this->run();
        try {
            return $st->fetchAll($mode);
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
            'from' => $this->tables,
            'join' => $this->joinTokens,
            'columns' => $this->columns,
            'distinct' => $this->distinct,
            'where' => $this->whereTokens,
            'having' => $this->havingTokens,
            'groupBy' => $this->groupBy,
            'orderBy' => array_values($this->orderBy),
            'limit' => $this->limit,
            'offset' => $this->offset,
            'union' => $this->unionTokens,
        ];
    }

    /**
     * @param FragmentInterface|string $field
     * @param string                   $order Sorting direction, ASC|DESC.
     *
     * @return $this|self
     */
    private function addOrder($field, string $order): self
    {
        if (!is_string($field)) {
            $this->orderBy[] = [$field, $order];
        } elseif (!array_key_exists($field, $this->orderBy)) {
            $this->orderBy[$field] = [$field, $order];
        }
        return $this;
    }

    /**
     * @param string $method
     * @param string $column
     *
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
