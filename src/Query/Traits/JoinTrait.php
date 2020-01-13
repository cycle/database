<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query\Traits;

use Closure;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Injection\Expression;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\ActiveQuery;

/**
 * Provides ability to generate QueryCompiler JOIN tokens including ON conditions and table/column
 * aliases.
 *
 * Simple joins (ON userID = users.id):
 * $select->join('LEFT', 'info', 'userID', 'users.id');
 * $select->leftJoin('info', 'userID', '=', 'users.id');
 * $select->rightJoin('info', ['userID' => 'users.id']);
 *
 * More complex ON conditions:
 * $select->leftJoin('info', function($select) {
 *      $select->on('userID', 'users.id')->orOn('userID', 'users.masterID');
 * });
 *
 * To specify on conditions outside join method use "on" methods.
 * $select->leftJoin('info')->on('userID', '=', 'users.id');
 *
 * On methods will only support conditions based on outer table columns. You can not use parametric
 * values here, use "on where" conditions instead.
 * $select->leftJoin('info')->on('userID', '=', 'users.id')->onWhere('value', 100);
 *
 * Arguments and syntax in "on" and "onWhere" conditions is identical to "where" method defined in
 * AbstractWhere.
 * Attention, "on" and "onWhere" conditions will be applied to last registered join only!
 *
 * You can also use table aliases and use them in conditions after:
 * $select->join('LEFT', 'info as i')->on('i.userID', 'users.id');
 * $select->join('LEFT', 'info as i', function($select) {
 *      $select->on('i.userID', 'users.id')->orOn('i.userID', 'users.masterID');
 * });
 *
 * @see AbstractWhere
 */
trait JoinTrait
{

    /**
     * Set of join tokens with on and on where conditions associated, must be supported by
     * QueryCompilers.
     *
     * @var array
     */
    protected $joinTokens = [];
    /**
     * Name/id of last join, every ON and ON WHERE call will be associated with this join.
     *
     * @var string
     */
    private $lastJoin = null;

    /**
     * Register new JOIN with specified type with set of on conditions (linking one table to
     * another, no parametric on conditions allowed here).
     *
     * @param string|ActiveQuery $type    Join type. Allowed values, LEFT, RIGHT, INNER and etc.
     * @param string             $outer   Joined table name (without prefix), may include AS statement.
     * @param string             $alias   Joined table or query alias.
     * @param mixed              $on      Simplified on definition linking table names (no
     *                                    parameters allowed) or closure.
     * @return $this
     *
     * @throws BuilderException
     */
    public function join($type, $outer, string $alias = null, $on = null): self
    {
        $this->joinTokens[++$this->lastJoin] = [
            'outer' => $outer,
            'alias' => $alias,
            'type'  => strtoupper($type),
            'on'    => []
        ];

        return call_user_func_array([$this, 'on'], array_slice(func_get_args(), 2));
    }

    /**
     * Register new INNER JOIN with set of on conditions (linking one table to another, no
     * parametric on conditions allowed here).
     *
     * @link http://www.w3schools.com/sql/sql_join_inner.asp
     * @see  join()
     *
     * @param string|ActiveQuery $outer Joined table name (without prefix), may include AS statement.
     * @param string             $alias Joined table or query alias.
     * @return $this
     *
     * @throws BuilderException
     */
    public function innerJoin($outer, string $alias = null): self
    {
        $this->joinTokens[++$this->lastJoin] = [
            'outer' => $outer,
            'alias' => $alias,
            'type'  => 'INNER',
            'on'    => []
        ];

        return $this;
    }

    /**
     * Register new RIGHT JOIN with set of on conditions (linking one table to another, no
     * parametric on conditions allowed here).
     *
     * @link http://www.w3schools.com/sql/sql_join_right.asp
     * @see  join()
     *
     * @param string|ActiveQuery $outer   Joined table name (without prefix), may include AS statement.
     * @param string             $alias   Joined table or query alias.
     * @param mixed              $on      Simplified on definition linking table names (no
     *                                    parameters allowed) or closure.
     * @return $this
     *
     * @throws BuilderException
     */
    public function rightJoin($outer, string $alias = null, $on = null): self
    {
        $this->joinTokens[++$this->lastJoin] = [
            'outer' => $outer,
            'alias' => $alias,
            'type'  => 'RIGHT',
            'on'    => []
        ];

        return $this;
    }

    /**
     * Register new LEFT JOIN with set of on conditions (linking one table to another, no
     * parametric
     * on conditions allowed here).
     *
     * @link http://www.w3schools.com/sql/sql_join_left.asp
     * @see  join()
     *
     * @param string|ActiveQuery $outer Joined table name (without prefix), may include AS statement.
     * @param string             $alias Joined table or query alias.
     * @return $this
     *
     * @throws BuilderException
     */
    public function leftJoin($outer, string $alias = null): self
    {
        $this->joinTokens[++$this->lastJoin] = [
            'outer' => $outer,
            'alias' => $alias,
            'type'  => 'LEFT',
            'on'    => []
        ];

        return $this;
    }

    /**
     * Register new FULL JOIN with set of on conditions (linking one table to another, no
     * parametric
     * on conditions allowed here).
     *
     * @link http://www.w3schools.com/sql/sql_join_full.asp
     * @see  join()
     *
     * @param string|ActiveQuery $outer Joined table name (without prefix), may include AS statement.
     * @param string             $alias Joined table or query alias.
     * @return $this
     *
     * @throws BuilderException
     */
    public function fullJoin($outer, string $alias = null): self
    {
        $this->joinTokens[++$this->lastJoin] = [
            'outer' => $outer,
            'alias' => $alias,
            'type'  => 'FULL',
            'on'    => []
        ];

        return $this;
    }

    /**
     * Simple ON condition with various set of arguments. Can only be used to link column values
     * together, no parametric values allowed.
     *
     * @param mixed ...$args [(column, outer column), (column, operator, outer column)]
     * @return $this
     *
     * @throws BuilderException
     */
    public function on(...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->joinTokens[$this->lastJoin]['on'],
            $this->onWrapper()
        );

        return $this;
    }

    /**
     * Simple AND ON condition with various set of arguments. Can only be used to link column values
     * together, no parametric values allowed.
     *
     * @param mixed ...$args [(column, outer column), (column, operator, outer column)]
     * @return $this
     *
     * @throws BuilderException
     */
    public function andOn(...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->joinTokens[$this->lastJoin]['on'],
            $this->onWrapper()
        );

        return $this;
    }

    /**
     * Simple OR ON condition with various set of arguments. Can only be used to link column values
     * together, no parametric values allowed.
     *
     * @param mixed ...$args [(column, outer column), (column, operator, outer column)]
     * @return $this
     *
     * @throws BuilderException
     */
    public function orOn(...$args): self
    {
        $this->registerToken(
            'OR',
            $args,
            $this->joinTokens[$this->lastJoin]['on'],
            $this->onWrapper()
        );

        return $this;
    }

    /**
     * Simple ON WHERE condition with various set of arguments. You can use parametric values in
     * such methods.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return $this
     *
     * @throws BuilderException
     * @see AbstractWhere
     *
     */
    public function onWhere(...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->joinTokens[$this->lastJoin]['on'],
            $this->onWhereWrapper()
        );

        return $this;
    }

    /**
     * Simple AND ON WHERE condition with various set of arguments. You can use parametric values in
     * such methods.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return $this
     *
     * @throws BuilderException
     * @see AbstractWhere
     *
     */
    public function andOnWhere(...$args): self
    {
        $this->registerToken(
            'AND',
            $args,
            $this->joinTokens[$this->lastJoin]['on'],
            $this->onWhereWrapper()
        );

        return $this;
    }

    /**
     * Simple OR ON WHERE condition with various set of arguments. You can use parametric values in
     * such methods.
     *
     * @param mixed ...$args [(column, value), (column, operator, value)]
     * @return $this
     *
     * @throws BuilderException
     * @see AbstractWhere
     *
     */
    public function orOnWhere(...$args): self
    {
        $this->registerToken(
            'OR',
            $args,
            $this->joinTokens[$this->lastJoin]['on'],
            $this->onWhereWrapper()
        );

        return $this;
    }

    /**
     * Convert various amount of where function arguments into valid where token.
     *
     * @param string   $boolean    Boolean joiner (AND | OR).
     * @param array    $params     Set of parameters collected from where functions.
     * @param array    $tokens     Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper    Callback or closure used to wrap/collect every potential
     *                             parameter.
     *
     * @throws BuilderException
     */
    abstract protected function registerToken(
        $boolean,
        array $params,
        &$tokens,
        callable $wrapper
    );

    /**
     * Convert parameters used in JOIN ON statements into sql expressions.
     *
     * @return Closure
     */
    private function onWrapper(): Closure
    {
        return static function ($parameter) {
            if ($parameter instanceof FragmentInterface || $parameter instanceof ParameterInterface) {
                return $parameter;
            }

            return new Expression($parameter);
        };
    }

    /**
     * Applied to every potential parameter while ON WHERE tokens generation.
     *
     * @return Closure
     */
    private function onWhereWrapper(): Closure
    {
        return static function ($parameter) {
            if (is_array($parameter)) {
                throw new BuilderException(
                    'Arrays must be wrapped with Parameter instance'
                );
            }

            //Wrapping all values with ParameterInterface
            if (!$parameter instanceof ParameterInterface && !$parameter instanceof FragmentInterface) {
                $parameter = new Parameter($parameter);
            }

            return $parameter;
        };
    }
}
