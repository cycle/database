<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query\Traits;

use Spiral\Database\Driver\Compiler;
use Spiral\Database\Exception\BuilderException;

trait TokenTrait
{
    /**
     * Convert various amount of where function arguments into valid where token.
     *
     * @param string   $joiner     Boolean joiner (AND | OR).
     * @param array    $parameters Set of parameters collected from where functions.
     * @param array    $tokens     Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper    Callback or closure used to wrap/collect every potential
     *                             parameter.
     *
     * @throws BuilderException
     * @see AbstractWhere
     *
     */
    protected function createToken($joiner, array $parameters, &$tokens, callable $wrapper): void
    {
        [$identifier, $a, $b, $c] = $parameters + array_fill(0, 5, null);

        if ($identifier === null) {
            //Nothing to do
            return;
        }

        //Where conditions specified in array form
        if (is_array($identifier)) {
            if (count($identifier) === 0) {
                // nothing to do
                return;
            }

            if (count($identifier) === 1) {
                $this->arrayWhere(
                    $joiner === 'AND' ? Compiler::TOKEN_AND : Compiler::TOKEN_OR,
                    $identifier,
                    $tokens,
                    $wrapper
                );

                return;
            }

            $tokens[] = [$joiner, '('];
            $this->arrayWhere(Compiler::TOKEN_AND, $identifier, $tokens, $wrapper);
            $tokens[] = ['', ')'];

            return;
        }

        if ($identifier instanceof \Closure) {
            $tokens[] = [$joiner, '('];
            call_user_func($identifier, $this, $joiner, $wrapper);
            $tokens[] = ['', ')'];

            return;
        }

        switch (count($parameters)) {
            case 1:
                //AND|OR [identifier: sub-query]
                $tokens[] = [$joiner, $identifier];
                break;
            case 2:
                //AND|OR [identifier] = [valueA]
                $tokens[] = [$joiner, [$identifier, '=', $wrapper($a)]];
                break;
            case 3:
                if (is_string($a)) {
                    $a = strtoupper($a);
                    if (in_array($a, ['BETWEEN', 'NOT BETWEEN'])) {
                        throw new BuilderException('Between statements expects exactly 2 values');
                    }
                } elseif (is_scalar($a)) {
                    $a = strval($a);
                }

                //AND|OR [identifier] [valueA: OPERATION] [valueA]
                $tokens[] = [$joiner, [$identifier, $a, $wrapper($b)]];
                break;
            case 4:
                //BETWEEN or NOT BETWEEN
                if (!is_string($a) || !in_array(strtoupper($a), ['BETWEEN', 'NOT BETWEEN'])) {
                    throw new BuilderException(
                        'Only "BETWEEN" or "NOT BETWEEN" can define second comparision value'
                    );
                }

                //AND|OR [identifier] [valueA: BETWEEN|NOT BETWEEN] [b] [valueC]
                $tokens[] = [$joiner, [$identifier, strtoupper($a), $wrapper($b), $wrapper($c)]];
        }
    }

    /**
     * Convert simplified where definition into valid set of where tokens.
     *
     * @param string   $grouper Grouper type (see self::TOKEN_AND, self::TOKEN_OR).
     * @param array    $where   Simplified where definition.
     * @param array    $tokens  Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper Callback or closure used to wrap/collect every potential
     *                          parameter.
     *
     * @throws BuilderException
     * @see AbstractWhere
     */
    private function arrayWhere(string $grouper, array $where, &$tokens, callable $wrapper): void
    {
        $joiner = ($grouper === Compiler::TOKEN_AND ? 'AND' : 'OR');

        foreach ($where as $key => $value) {
            $token = strtoupper($key);

            //Grouping identifier (@OR, @AND), MongoDB like style
            if ($token === Compiler::TOKEN_AND || $token === Compiler::TOKEN_OR) {
                $tokens[] = [$joiner, '('];

                foreach ($value as $nested) {
                    if (count($nested) === 1) {
                        $this->arrayWhere($token, $nested, $tokens, $wrapper);
                        continue;
                    }

                    $tokens[] = [$token === Compiler::TOKEN_AND ? 'AND' : 'OR', '('];
                    $this->arrayWhere(Compiler::TOKEN_AND, $nested, $tokens, $wrapper);
                    $tokens[] = ['', ')'];
                }

                $tokens[] = ['', ')'];

                continue;
            }

            //AND|OR [name] = [value]
            if (!is_array($value)) {
                $tokens[] = [$joiner, [$key, '=', $wrapper($value)]];
                continue;
            }

            if (count($value) > 1) {
                //Multiple values to be joined by AND condition (x = 1, x != 5)
                $tokens[] = [$joiner, '('];
                $this->builtConditions('AND', $key, $value, $tokens, $wrapper);
                $tokens[] = ['', ')'];
            } else {
                $this->builtConditions($joiner, $key, $value, $tokens, $wrapper);
            }
        }
    }

    /**
     * Build set of conditions for specified identifier.
     *
     * @param string   $innerJoiner Inner boolean joiner.
     * @param string   $key         Column identifier.
     * @param array    $where       Operations associated with identifier.
     * @param array    $tokens      Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper     Callback or closure used to wrap/collect every potential
     *                              parameter.
     *
     * @return array
     *
     * @throws BuilderException
     */
    private function builtConditions(
        string $innerJoiner,
        string $key,
        $where,
        &$tokens,
        callable $wrapper
    ): array {
        foreach ($where as $operation => $value) {
            if (is_numeric($operation)) {
                throw new BuilderException('Nested conditions should have defined operator');
            }

            $operation = strtoupper($operation);
            if (!in_array($operation, ['BETWEEN', 'NOT BETWEEN'])) {
                //AND|OR [name] [OPERATION] [nestedValue]
                $tokens[] = [$innerJoiner, [$key, $operation, $wrapper($value)]];
                continue;
            }

            /*
             * Between and not between condition described using array of [left, right] syntax.
             */

            if (!is_array($value) || count($value) != 2) {
                throw new BuilderException(
                    'Exactly 2 array values are required for between statement'
                );
            }

            $tokens[] = [
                //AND|OR [name] [BETWEEN|NOT BETWEEN] [value 1] [value 2]
                $innerJoiner,
                [$key, $operation, $wrapper($value[0]), $wrapper($value[1])],
            ];
        }

        return $tokens;
    }
}
