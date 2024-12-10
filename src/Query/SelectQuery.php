<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\Traits\WhereJsonTrait;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\Traits\HavingTrait;
use Cycle\Database\Query\Traits\JoinTrait;
use Cycle\Database\Query\Traits\TokenTrait;
use Cycle\Database\Query\Traits\WhereTrait;
use Cycle\Database\StatementInterface;
use Spiral\Pagination\PaginableInterface;

/**
 * Builds select sql statements.
 */
class SelectQuery extends ActiveQuery implements
    \Countable,
    \IteratorAggregate,
    PaginableInterface
{
    use HavingTrait;
    use JoinTrait;
    use TokenTrait;
    use WhereJsonTrait;
    use WhereTrait;

    // sort directions
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    protected array $tables = [];
    protected array $unionTokens = [];
    protected array $exceptTokens = [];
    protected array $intersectTokens = [];
    protected bool|string|array $distinct = false;
    protected array $columns = ['*'];

    /** @var FragmentInterface[][]|string[][] */
    protected array $orderBy = [];

    protected array $groupBy = [];
    protected bool $forUpdate = false;
    private ?int $limit = null;
    private ?int $offset = null;

    /**
     * @param array $from Initial set of table names.
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
     */
    public function distinct(bool|string|FragmentInterface $distinct = true): self
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Set table names SELECT query should be performed for. Table names can be provided with
     * specified alias (AS construction).
     */
    public function from(mixed $tables): self
    {
        $this->tables = $this->fetchIdentifiers(\func_get_args());

        return $this;
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Set columns should be fetched as result of SELECT query. Columns can be provided with
     * specified alias (AS construction).
     */
    public function columns(mixed $columns): self
    {
        $this->columns = $this->fetchIdentifiers(\func_get_args());

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Select entities for the following update.
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
     *      'name' => SelectQuery::SORT_ASC,
     *
     *      // The following options below have the same effect (Direction will be ignored)
     *      new Fragment('RAND()') => null,
     *      new Fragment('RAND()')
     * ]);
     *
     * $select->orderBy('name', SelectQuery::SORT_ASC);
     *
     * $select->orderBy(new Fragment('RAND()'), null); // direction will be ignored
     * $select->orderBy(new Fragment('RAND()'), 'ASC NULLS LAST'); // Postgres specific directions are also supported
     *
     * @param 'ASC'|'DESC'|null $direction Sorting direction
     */
    public function orderBy(string|FragmentInterface|array $expression, ?string $direction = self::SORT_ASC): self
    {
        if (!\is_array($expression)) {
            $this->addOrder($expression, $direction);
            return $this;
        }

        foreach ($expression as $nested => $dir) {
            // support for orderBy([new Fragment('RAND()')]) without passing direction
            if (\is_int($nested)) {
                $nested = $dir;
                $dir = null;
            }

            $this->addOrder($nested, $dir);
        }

        return $this;
    }

    /**
     * Column or expression to group query by.
     */
    public function groupBy(string|Fragment|Expression $expression): self
    {
        $this->groupBy[] = $expression;

        return $this;
    }

    /**
     * Add select query to be united with.
     */
    public function union(FragmentInterface $query): self
    {
        $this->unionTokens[] = ['', $query];

        return $this;
    }

    /**
     * Add select query to be united with. Duplicate values will be included in result.
     */
    public function unionAll(FragmentInterface $query): self
    {
        $this->unionTokens[] = ['ALL', $query];

        return $this;
    }

    /**
     * Add select query to be intersected with.
     */
    public function intersect(FragmentInterface $query): self
    {
        $this->intersectTokens[] = ['', $query];

        return $this;
    }

    /**
     * Add select query to be intersected with. Duplicate values will be included in result.
     */
    public function intersectAll(FragmentInterface $query): self
    {
        $this->intersectTokens[] = ['ALL', $query];

        return $this;
    }

    /**
     * Add select query to be excepted with.
     */
    public function except(FragmentInterface $query): self
    {
        $this->exceptTokens[] = ['', $query];

        return $this;
    }

    /**
     * Add select query to be excepted with. Duplicate values will be included in result.
     */
    public function exceptAll(FragmentInterface $query): self
    {
        $this->exceptTokens[] = ['ALL', $query];

        return $this;
    }

    /**
     * Set selection limit. Attention, this limit value does not affect values set in paginator but
     * only changes pagination window. Set to 0 to disable limiting.
     */
    public function limit(?int $limit = null): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Set selection offset. Attention, this value does not affect associated paginator but only
     * changes pagination window.
     */
    public function offset(?int $offset = null): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

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
     * @throws \Throwable
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
                $count,
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
     * @psalm-param non-empty-string $column Column to count by (every column by default).
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
            return (int) $st->fetchColumn();
        } finally {
            $st->close();
        }
    }

    /**
     * @psalm-param non-empty-string $column
     */
    public function avg(string $column): mixed
    {
        return $this->runAggregate('AVG', $column);
    }

    /**
     * @psalm-param non-empty-string $column
     */
    public function max(string $column): mixed
    {
        return $this->runAggregate('MAX', $column);
    }

    /**
     * @psalm-param non-empty-string $column
     */
    public function min(string $column): mixed
    {
        return $this->runAggregate('MIN', $column);
    }

    /**
     * @psalm-param non-empty-string $column
     */
    public function sum(string $column): mixed
    {
        return $this->runAggregate('SUM', $column);
    }

    public function getIterator(): StatementInterface
    {
        return $this->run();
    }

    /**
     * Request all results as array.
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

    public function getType(): int
    {
        return CompilerInterface::SELECT_QUERY;
    }

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
            'orderBy'   => \array_values($this->orderBy),
            'limit'     => $this->limit,
            'offset'    => $this->offset,
            'union'     => $this->unionTokens,
            'intersect' => $this->intersectTokens,
            'except'    => $this->exceptTokens,
        ];
    }

    /**
     * @param string|null $order Sorting direction, ASC|DESC|null.
     *
     * @return $this|self
     */
    private function addOrder(string|FragmentInterface $field, ?string $order): self
    {
        if (!\is_string($field)) {
            $this->orderBy[] = [$field, $order];
        } elseif (!\array_key_exists($field, $this->orderBy)) {
            $this->orderBy[$field] = [$field, $order];
        }
        return $this;
    }

    /**
     * @psalm-param non-empty-string $method
     * @psalm-param non-empty-string $column
     */
    private function runAggregate(string $method, string $column): mixed
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
