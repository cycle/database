<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query\Traits;

use Closure;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\Parameter;

trait TokenTrait
{
    /**
     * Convert various amount of where function arguments into valid where token.
     *
     * @psalm-param non-empty-string $boolean Boolean joiner (AND | OR).
     *
     * @param array $params Set of parameters collected from where functions.
     * @param array $tokens Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper Callback or closure used to wrap/collect every potential parameter.
     *
     * @throws BuilderException
     */
    protected function registerToken(string $boolean, array $params, array &$tokens, callable $wrapper): void
    {
        $count = \count($params);
        if ($count === 0) {
            // nothing to do
            return;
        }

        if ($count === 1) {
            $complex = $params[0];

            if ($complex === null) {
                return;
            }

            if (\is_array($complex)) {
                if (\count($complex) === 0) {
                    // nothing to do
                    return;
                }

                if (\count($complex) === 1) {
                    $this->flattenWhere($this->booleanToToken($boolean), $complex, $tokens, $wrapper);
                    return;
                }

                $tokens[] = [$boolean, '('];

                $this->flattenWhere(
                    CompilerInterface::TOKEN_AND,
                    $complex,
                    $tokens,
                    $wrapper,
                );

                $tokens[] = ['', ')'];

                return;
            }

            if ($complex instanceof \Closure) {
                $tokens[] = [$boolean, '('];
                $complex($this, $boolean, $wrapper);
                $tokens[] = ['', ')'];
                return;
            }

            if ($complex instanceof FragmentInterface) {
                $tokens[] = [$boolean, $complex];
                return;
            }

            throw new BuilderException('Expected array where or closure');
        }

        switch ($count) {
            case 2:
                // AND|OR [name] = [valueA]
                $tokens[] = [
                    $boolean,
                    [$params[0], '=', $wrapper($params[1])],
                ];
                break;
            case 3:
                [$name, $operator, $value] = $params;

                if (\is_string($operator)) {
                    $operator = \strtoupper($operator);
                    if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
                        throw new BuilderException('Between statements expects exactly 2 values');
                    }
                    if (\is_array($value) && \in_array($operator, ['IN', 'NOT IN'], true)) {
                        $value = new Parameter($value);
                    }
                } elseif (\is_scalar($operator)) {
                    $operator = (string) $operator;
                }

                // AND|OR [name] [valueA: OPERATION] [valueA]
                $tokens[] = [
                    $boolean,
                    [$name, $operator, $wrapper($value)],
                ];
                break;
            case 4:
                [$name, $operator] = $params;
                if (!\is_string($operator)) {
                    throw new BuilderException('Invalid operator type, string expected');
                }

                $operator = \strtoupper($operator);
                if ($operator !== 'BETWEEN' && $operator !== 'NOT BETWEEN') {
                    throw new BuilderException(
                        'Only "BETWEEN" or "NOT BETWEEN" can define second comparision value',
                    );
                }

                // AND|OR [name] [valueA: BETWEEN|NOT BETWEEN] [value] [valueC]
                $tokens[] = [
                    $boolean,
                    [
                        $name,
                        \strtoupper($operator),
                        $wrapper($params[2]),
                        $wrapper($params[3]),
                    ],
                ];
                break;
            default:
                throw new BuilderException('Invalid where method call');
        }
    }

    /**
     * Convert simplified where definition into valid set of where tokens.
     *
     * @psalm-param non-empty-string $grouper Grouper type (see self::TOKEN_AND, self::TOKEN_OR).
     *
     * @param array $where Simplified where definition.
     * @param array $tokens Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper Callback or closure used to wrap/collect every potential parameter.
     *
     * @throws BuilderException
     */
    private function flattenWhere(string $grouper, array $where, array &$tokens, callable $wrapper): void
    {
        $boolean = $this->tokenToBoolean($grouper);

        foreach ($where as $key => $value) {
            // Support for closures
            if (\is_int($key) && $value instanceof \Closure) {
                $tokens[] = [$boolean, '('];
                $value($this, $boolean, $wrapper);
                $tokens[] = ['', ')'];
                continue;
            }

            $token = \strtoupper($key);

            // Grouping identifier (@OR, @AND), MongoDB like style
            if (
                $token === CompilerInterface::TOKEN_AND ||
                $token === CompilerInterface::TOKEN_OR ||
                $token === CompilerInterface::TOKEN_AND_NOT ||
                $token === CompilerInterface::TOKEN_OR_NOT
            ) {
                $tokens[] = [$boolean, '('];

                foreach ($value as $nested) {
                    if (\count($nested) === 1) {
                        $this->flattenWhere($token, $nested, $tokens, $wrapper);
                        continue;
                    }

                    $tokens[] = [$this->tokenToBoolean($token), '('];
                    $this->flattenWhere(CompilerInterface::TOKEN_AND, $nested, $tokens, $wrapper);
                    $tokens[] = ['', ')'];
                }

                $tokens[] = ['', ')'];

                continue;
            }

            // AND|OR [name] = [value]
            if (!\is_array($value)) {
                $tokens[] = [
                    $boolean,
                    [$key, '=', $wrapper($value)],
                ];
                continue;
            }

            if (\count($value) === 1) {
                $this->pushCondition(
                    $boolean,
                    $key,
                    $value,
                    $tokens,
                    $wrapper,
                );
                continue;
            }

            //Multiple values to be joined by AND condition (x = 1, x != 5)
            $tokens[] = [$boolean, '('];
            $this->pushCondition('AND', $key, $value, $tokens, $wrapper);
            $tokens[] = ['', ')'];
        }
    }

    /**
     * Build set of conditions for specified identifier.
     *
     * @psalm-param non-empty-string $innerJoiner Inner boolean joiner.
     * @psalm-param non-empty-string $key Column identifier.
     *
     * @param array $where Operations associated with identifier.
     * @param array $tokens Array to aggregate compiled tokens. Reference.
     * @param callable $wrapper Callback or closure used to wrap/collect every potential parameter.
     */
    private function pushCondition(string $innerJoiner, string $key, array $where, &$tokens, callable $wrapper): array
    {
        foreach ($where as $operation => $value) {
            if (\is_numeric($operation)) {
                throw new BuilderException('Nested conditions should have defined operator');
            }

            $operation = \strtoupper($operation);
            if ($operation !== 'BETWEEN' && $operation !== 'NOT BETWEEN') {
                // AND|OR [name] [OPERATION] [nestedValue]
                if (\is_array($value) && \in_array($operation, ['IN', 'NOT IN'], true)) {
                    $value = new Parameter($value);
                }
                $tokens[] = [
                    $innerJoiner,
                    [$key, $operation, $wrapper($value)],
                ];
                continue;
            }

            // Between and not between condition described using array of [left, right] syntax.
            if (!\is_array($value) || \count($value) !== 2) {
                throw new BuilderException(
                    'Exactly 2 array values are required for between statement',
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

    private function tokenToBoolean(string $token): string
    {
        return match ($token) {
            CompilerInterface::TOKEN_AND => 'AND',
            CompilerInterface::TOKEN_AND_NOT => 'AND NOT',
            CompilerInterface::TOKEN_OR_NOT => 'OR NOT',
            default => 'OR',
        };
    }

    private function booleanToToken(string $boolean): string
    {
        return match ($boolean) {
            'AND' => CompilerInterface::TOKEN_AND,
            'AND NOT' => CompilerInterface::TOKEN_AND_NOT,
            'OR NOT' => CompilerInterface::TOKEN_OR_NOT,
            default => 'OR',
        };
    }
}
