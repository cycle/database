<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\JsonExpression;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\QueryInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Query\SelectQuery;

/**
 * Caches calculated queries. Code in this class is performance optimized.
 */
final class CompilerCache implements CompilerInterface
{
    private array $cache = [];

    public function __construct(
        private CachingCompilerInterface $compiler,
    ) {}

    /**
     * @psalm-param non-empty-string $identifier
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->compiler->quoteIdentifier($identifier);
    }

    /**
     * @psalm-return non-empty-string
     */
    public function compile(QueryParameters $params, string $prefix, FragmentInterface $fragment): string
    {
        if ($fragment->getType() === self::SELECT_QUERY) {
            $queryHash = $prefix . $this->hashSelectQuery($params, $fragment->getTokens());

            if (isset($this->cache[$queryHash])) {
                return $this->cache[$queryHash];
            }

            return $this->cache[$queryHash] = $this->compiler->compile(
                new QueryParameters(),
                $prefix,
                $fragment,
            );
        }

        if ($fragment->getType() === self::INSERT_QUERY) {
            $tokens = $fragment->getTokens();

            if (\count($tokens['values']) === 1) {
                $queryHash = $prefix . $this->hashInsertQuery($params, $tokens);
                if (isset($this->cache[$queryHash])) {
                    return $this->cache[$queryHash];
                }

                return $this->cache[$queryHash] = $this->compiler->compile(
                    new QueryParameters(),
                    $prefix,
                    $fragment,
                );
            }
        }

        return $this->compiler->compile(
            $params,
            $prefix,
            $fragment,
        );
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function hashInsertQuery(QueryParameters $params, array $tokens): string
    {
        foreach ((array) ($tokens['return'] ?? []) as $return) {
            if ($return instanceof FragmentInterface) {
                foreach ($return->getTokens()['parameters'] as $param) {
                    $params->push($param);
                }
            }
        }

        $hash = \sprintf(
            'i_%s%s_r%s',
            $tokens['table'],
            \implode('_', $tokens['columns']),
            \implode('_', (array) ($tokens['return'] ?? [])),
        );
        foreach ($tokens['values'] as $value) {
            if ($value instanceof FragmentInterface) {
                if ($value instanceof Expression || $value instanceof Fragment) {
                    foreach ($tokens['parameters'] as $param) {
                        $params->push($param);
                    }
                }

                $hash .= $value;
                continue;
            }

            if (!$value instanceof ParameterInterface) {
                $value = new Parameter($value);
            }

            if ($value->isArray()) {
                foreach ($value->getValue() as $child) {
                    if ($child instanceof FragmentInterface) {
                        if ($child instanceof \Stringable) {
                            $hash .= '_F_' . $child;
                        }
                        continue;
                    }

                    if (!$child instanceof ParameterInterface) {
                        $child = new Parameter($child);
                    }

                    $params->push($child);
                }

                $hash .= 'P?';
                continue;
            }

            $params->push($value);
            $hash .= 'P?';
        }

        return $hash;
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function hashSelectQuery(QueryParameters $params, array $tokens): string
    {
        // stable part of hash
        if (\is_array($tokens['distinct']) && isset($tokens['distinct']['on'])) {
            $hash = 's_' . $tokens['forUpdate'] . '_on_' . $tokens['distinct']['on'];
        } else {
            $hash = 's_' . $tokens['forUpdate'] . '_' . $tokens['distinct'];
        }

        foreach ($tokens['from'] as $table) {
            if ($table instanceof SelectQuery) {
                $hash .= 's_' . ($table->getPrefix() ?? '');
                $hash .= $this->hashSelectQuery($params, $table->getTokens());
                continue;
            }

            $hash .= $table;
        }

        $hash .= $this->hashColumns($params, $tokens['columns']);

        foreach ($tokens['join'] as $join) {
            $hash .= 'j' . $join['alias'] . \str_replace(['JOIN', ' '], '', $join['type']);

            if ($join['outer'] instanceof SelectQuery) {
                $hash .= $join['outer']->getPrefix() === null ? '' : 'p_' . $join['outer']->getPrefix();
                $hash .= $this->hashSelectQuery($params, $join['outer']->getTokens());
            } else {
                $hash .= $join['outer'];
            }

            $hash .= 'on' . $this->hashWhere($params, $join['on']);
        }

        if ($tokens['where'] !== []) {
            $hash .= 'w' . $this->hashWhere($params, $tokens['where']);
        }

        if ($tokens['having'] !== []) {
            $hash .= 'h' . $this->hashWhere($params, $tokens['having']);
        }

        $hash .= \implode(',', $tokens['groupBy']);

        $hash .= $this->hashOrderBy($params, $tokens['orderBy']);

        $hash .= $this->compiler->hashLimit($params, $tokens);

        foreach ($tokens['union'] as $union) {
            $hash .= $union[0];
            if ($union[1] instanceof SelectQuery) {
                $hash .= $union[1]->getPrefix() === null ? '' : 'p_' . $union[1]->getPrefix();
                $hash .= $this->hashSelectQuery($params, $union[1]->getTokens());
                continue;
            }

            $hash .= $union[1];
        }

        foreach ($tokens['intersect'] as $intersect) {
            $hash .= $intersect[0];
            if ($intersect[1] instanceof SelectQuery) {
                $hash .= $intersect[1]->getPrefix() === null ? '' : 'i_' . $intersect[1]->getPrefix();
                $hash .= $this->hashSelectQuery($params, $intersect[1]->getTokens());
                continue;
            }

            $hash .= $intersect[1];
        }

        foreach ($tokens['except'] as $except) {
            $hash .= $except[0];
            if ($except[1] instanceof SelectQuery) {
                $hash .= $except[1]->getPrefix() === null ? '' : 'e_' . $except[1]->getPrefix();
                $hash .= $this->hashSelectQuery($params, $except[1]->getTokens());
                continue;
            }

            $hash .= $except[1];
        }

        return $hash;
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function hashWhere(QueryParameters $params, array $where): string
    {
        $hash = '';
        foreach ($where as $condition) {
            // OR/AND keyword
            [$boolean, $context] = $condition;

            $hash .= $boolean;
            if (\is_string($context)) {
                $hash .= $context;
                continue;
            }

            if ($context instanceof FragmentInterface) {
                if (
                    $context instanceof Expression ||
                    $context instanceof Fragment ||
                    $context instanceof JsonExpression
                ) {
                    foreach ($context->getTokens()['parameters'] as $param) {
                        $params->push($param);
                    }
                }

                $hash .= $context;
                continue;
            }

            if ($context[0] instanceof QueryInterface) {
                $hash .= $context[0]->getPrefix() === null ? '' : 'p_' . $context[0]->getPrefix();
                $hash .= $this->hashSelectQuery($params, $context[0]->getTokens());
            } elseif ($context[0] instanceof ParameterInterface) {
                $hash .= $this->hashParam($params, $context[0]);
            } else {
                if ($context[0] instanceof Expression || $context[0] instanceof Fragment) {
                    foreach ($context[0]->getTokens()['parameters'] as $param) {
                        $params->push($param);
                    }
                }

                $hash .= $context[0];
            }

            // operator
            if ($context[1] instanceof Expression || $context[1] instanceof Fragment) {
                foreach ($context[1]->getTokens()['parameters'] as $param) {
                    $params->push($param);
                }
            }

            $hash .= $context[1];

            if ($context[2] instanceof QueryInterface) {
                $hash .= $context[2]->getPrefix() === null ? '' : 'p_' . $context[2]->getPrefix();
                $hash .= $this->hashSelectQuery($params, $context[2]->getTokens());
            } elseif ($context[2] instanceof ParameterInterface) {
                $hash .= $this->hashParam($params, $context[2]);
            } else {
                if ($context[2] instanceof Expression || $context[2] instanceof Fragment) {
                    foreach ($context[2]->getTokens()['parameters'] as $param) {
                        $params->push($param);
                    }
                }

                $hash .= $context[2];
            }

            if (isset($context[3])) {
                if ($context[3] instanceof QueryInterface) {
                    $hash .= $context[3]->getPrefix() === null ? '' : 'p_' . $context[3]->getPrefix();
                    $hash .= $this->hashSelectQuery($params, $context[3]->getTokens());
                } elseif ($context[3] instanceof ParameterInterface) {
                    $hash .= $this->hashParam($params, $context[3]);
                } else {
                    if ($context[3] instanceof Expression || $context[3] instanceof Fragment) {
                        foreach ($context[3]->getTokens()['parameters'] as $param) {
                            $params->push($param);
                        }
                    }

                    $hash .= $context[3];
                }
            }
        }

        return $hash;
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function hashColumns(QueryParameters $params, array $columns): string
    {
        $hash = '';
        foreach ($columns as $column) {
            if ($column instanceof Expression || $column instanceof Fragment) {
                foreach ($column->getTokens()['parameters'] as $param) {
                    $params->push($param);
                }
            }

            $hash .= (string) $column . ',';
        }

        return $hash;
    }

    /**
     * @psalm-return non-empty-string
     */
    private function hashParam(QueryParameters $params, ParameterInterface $param): string
    {
        if ($param->isNull()) {
            return 'N';
        }

        if ($param->isArray()) {
            $simpleParams = [];
            foreach ($param->getValue() as $value) {
                if ($value instanceof FragmentInterface) {
                    foreach ($value->getTokens()['parameters'] as $fragmentParam) {
                        $params->push($fragmentParam);
                    }
                } else {
                    $simpleParams[] = $value;
                }
            }

            if ($simpleParams !== []) {
                $params->push(new Parameter($simpleParams));
            }

            return 'A' . \count($param->getValue());
        }

        $params->push($param);

        return '?';
    }

    private function hashOrderBy(QueryParameters $params, array $tokens): string
    {
        $hash = '';
        foreach ($tokens as $order) {
            if ($order[0] instanceof FragmentInterface) {
                foreach ($order[0]->getTokens()['parameters'] as $param) {
                    $params->push($param);
                }
            }
            $hash .= $order[0] . $order[1];
        }

        return $hash;
    }
}
