<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Injection\Expression;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\QueryInterface;
use Spiral\Database\Query\QueryParameters;
use Spiral\Database\Query\SelectQuery;

/**
 * Caches calculated queries. Code in this class is performance optimized.
 */
final class CompilerCache implements CompilerInterface
{
    /** @var array */
    private $cache = [];

    /** @var CachingCompilerInterface */
    private $compiler;

    /**
     * @param CachingCompilerInterface $compiler
     */
    public function __construct(CachingCompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        return $this->compiler->quoteIdentifier($identifier);
    }

    /**
     * @param QueryParameters   $params
     * @param string            $prefix
     * @param FragmentInterface $fragment
     * @return string
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
                $fragment
            );
        }

        if ($fragment->getType() === self::INSERT_QUERY) {
            $tokens = $fragment->getTokens();

            if (count($tokens['values']) === 1) {
                $queryHash = $prefix . $this->hashInsertQuery($params, $tokens);
                if (isset($this->cache[$queryHash])) {
                    return $this->cache[$queryHash];
                }

                return $this->cache[$queryHash] = $this->compiler->compile(
                    new QueryParameters(),
                    $prefix,
                    $fragment
                );
            }
        }

        return $this->compiler->compile(
            $params,
            $prefix,
            $fragment
        );
    }

    /**
     * @param QueryParameters $params
     * @param array           $tokens
     * @return string
     */
    protected function hashInsertQuery(QueryParameters $params, array $tokens): string
    {
        $hash = 'i_' . $tokens['table'] . implode('_', $tokens['columns']) . '_r' . ($tokens['return'] ?? '');
        foreach ($tokens['values'] as $value) {
            if ($value instanceof FragmentInterface) {
                if ($value instanceof Expression) {
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
     * @param QueryParameters $params
     * @param array           $tokens
     * @return string
     */
    protected function hashSelectQuery(QueryParameters $params, array $tokens): string
    {
        // stable part of hash
        if (is_array($tokens['distinct']) && isset($tokens['distinct']['on'])) {
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

        foreach ($tokens['join'] as $join) {
            $hash .= 'j' . $join['alias'] . $join['type'];

            if ($join['outer'] instanceof SelectQuery) {
                $hash .= $join['outer']->getPrefix() === null ? '' : 'p_' . $join['outer']->getPrefix();
                $hash .= $this->hashSelectQuery($params, $join['outer']->getTokens());
                continue;
            }

            $hash .= $join['outer'];
            $hash .= 'on' . $this->hashWhere($params, $join['on']);
        }

        $hash .= implode(',', $tokens['columns']);

        if ($tokens['where'] !== []) {
            $hash .= 'w' . $this->hashWhere($params, $tokens['where']);
        }

        if ($tokens['having'] !== []) {
            $hash .= 'h' . $this->hashWhere($params, $tokens['having']);
        }

        $hash .= implode(',', $tokens['groupBy']);

        foreach ($tokens['orderBy'] as $order) {
            $hash .= $order[0] . $order[1];
        }

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

        return $hash;
    }

    /**
     * @param QueryParameters $params
     * @param array           $where
     * @return string
     */
    protected function hashWhere(QueryParameters $params, array $where): string
    {
        $hash = '';
        foreach ($where as $condition) {
            // OR/AND keyword
            [$boolean, $context] = $condition;

            $hash .= $boolean;
            if (is_string($context)) {
                $hash .= $context;
                continue;
            }

            if ($context instanceof FragmentInterface) {
                if ($context instanceof Expression) {
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
                if ($context[0] instanceof Expression) {
                    foreach ($context[0]->getTokens()['parameters'] as $param) {
                        $params->push($param);
                    }
                }

                $hash .= $context[0];
            }

            // operator
            if ($context[1] instanceof Expression) {
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
                if ($context[2] instanceof Expression) {
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
                    if ($context[3] instanceof Expression) {
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
     * @param QueryParameters    $params
     * @param ParameterInterface $param
     * @return string
     */
    private function hashParam(QueryParameters $params, ParameterInterface $param): string
    {
        if ($param->isNull()) {
            return 'N';
        }

        $params->push($param);

        if ($param->isArray()) {
            return 'A' . count($param->getValue());
        }

        return '?';
    }
}
