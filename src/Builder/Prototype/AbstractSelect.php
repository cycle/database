<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Builder\Prototype;

use Spiral\Database\Builder\QueryBuilder;
use Spiral\Database\Builder\Traits\JoinsTrait;
use Spiral\Database\Entity\QueryCompiler;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Injection\ExpressionInterface;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Pagination\PaginatorAwareInterface;
use Spiral\Pagination\Traits\LimitsTrait;
use Spiral\Pagination\Traits\PaginatorTrait;

/**
 * Prototype for select queries, include ability to cache, paginate or chunk results. Support WHERE,
 * JOIN, HAVING, ORDER BY, GROUP BY, UNION and DISTINCT statements. In addition only desired set
 * of columns can be selected. In addition select.
 *
 * @see AbstractWhere
 *
 * @method int avg($identifier) Perform aggregation (AVG) based on column or expression value.
 * @method int min($identifier) Perform aggregation (MIN) based on column or expression value.
 * @method int max($identifier) Perform aggregation (MAX) based on column or expression value.
 * @method int sum($identifier) Perform aggregation (SUM) based on column or expression value.
 *
 * @deprecated
 */
abstract class AbstractSelect extends AbstractWhere implements
    \IteratorAggregate,
    PaginatorAwareInterface
{
    use JoinsTrait, LimitsTrait, PaginatorTrait;

    /**
     * Query type.
     */
    const QUERY_TYPE = QueryCompiler::SELECT_QUERY;

    /**
     * Sort directions.
     */
    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

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
     * Set of generated having tokens, format must be supported by QueryCompilers.
     *
     * @see AbstractWhere
     *
     * @var array
     */
    protected $havingTokens = [];

    /**
     * Parameters collected while generating HAVING tokens, must be in a same order as parameters
     * in resulted query.
     *
     * @see AbstractWhere
     *
     * @var array
     */
    protected $havingParameters = [];

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
     */
    public function getParameters(): array
    {
        return $this->flattenParameters(array_merge(
            $this->onParameters,
            $this->whereParameters,
            $this->havingParameters
        ));
    }

    /**
     * Mark query to return only distinct results.
     *
     * @param bool|string $distinct You are only allowed to use string value for Postgres databases.
     *
     * @return self|$this
     */
    public function distinct($distinct = true): AbstractSelect
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Simple HAVING condition with various set of arguments.
     *
     * @see AbstractWhere
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function having(...$args): AbstractSelect
    {
        $this->whereToken('AND', $args, $this->havingTokens, $this->havingWrapper());

        return $this;
    }

    /**
     * Simple AND HAVING condition with various set of arguments.
     *
     * @see AbstractWhere
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function andHaving(...$args): AbstractSelect
    {
        $this->whereToken('AND', $args, $this->havingTokens, $this->havingWrapper());

        return $this;
    }

    /**
     * Simple OR HAVING condition with various set of arguments.
     *
     * @see AbstractWhere
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     *
     * @return self|$this
     *
     * @throws BuilderException
     */
    public function orHaving(...$args): AbstractSelect
    {
        $this->whereToken('OR', $args, $this->havingTokens, $this->havingWrapper());

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
     *
     * @return self|$this
     */
    public function orderBy($expression, $direction = self::SORT_ASC): AbstractSelect
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
     *
     * @return self|$this
     */
    public function groupBy($expression): AbstractSelect
    {
        $this->grouping[] = $expression;

        return $this;
    }

    /**
     * Applied to every potential parameter while having tokens generation.
     *
     * @return \Closure
     */
    private function havingWrapper()
    {
        return function ($parameter) {
            if ($parameter instanceof FragmentInterface) {

                //We are only not creating bindings for plan fragments
                if (!$parameter instanceof ParameterInterface && !$parameter instanceof QueryBuilder) {
                    return $parameter;
                }
            }

            if (is_array($parameter)) {
                throw new BuilderException('Arrays must be wrapped with Parameter instance');
            }

            //Wrapping all values with ParameterInterface
            if (!$parameter instanceof ParameterInterface && !$parameter instanceof ExpressionInterface) {
                $parameter = new Parameter($parameter, Parameter::DETECT_TYPE);
            };

            //Let's store to sent to driver when needed
            $this->havingParameters[] = $parameter;

            return $parameter;
        };
    }
}
