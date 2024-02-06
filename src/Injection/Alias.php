<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

use Cycle\Database\Query\ActiveQuery;
use Cycle\Database\Query\QueryParameters;

class Alias extends Fragment
{
    /**
     * @param non-empty-string $fragment
     */
    private function __construct(
        protected string $fragment,
        protected array $parameters = []
    ) {
        parent::__construct($fragment, ...$parameters);
    }

    /**
     * @param non-empty-string $alias
     * @param non-empty-string $column
     */
    public static function quoted(string $alias, string $column): static
    {
        return new static($column . ' AS ' . $alias);
    }

    /**
     * @param non-empty-string $alias
     */
    public static function query(string $alias, ActiveQuery $query): static
    {
        $parameters = new QueryParameters();
        $statement = $query->sqlStatement($parameters);

        return new static($statement . ' AS ' . $alias, $parameters->getParameters());
    }

    /**
     * @param non-empty-string $alias
     */
    public static function value(string $alias, mixed $value): static
    {
        return new static($value . ' AS ' . $alias, [$value]);
    }

    /**
     * @param non-empty-string $alias
     */
    public static function fragment(string $alias, FragmentInterface $fragment): static
    {
        $tokens = $fragment->getTokens();

        return new static($tokens['fragment'] . ' AS ' . $alias, $tokens['parameters']);
    }
}
