<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Query\Interpolator;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Query\SelectQuery;

class SubQuery implements FragmentInterface
{
    private SelectQuery $query;
    private string $alias;

    /** @var ParameterInterface[] */
    private array $parameters;

    public function __construct(SelectQuery $query, string $alias)
    {
        $this->query = $query;
        $this->alias = $alias;

        $parameters = new QueryParameters();
        $this->query->sqlStatement($parameters);
        $this->parameters = $parameters->getParameters();
    }

    public function getType(): int
    {
        return CompilerInterface::SUBQUERY;
    }

    public function getTokens(): array
    {
        return \array_merge(
            [
                'alias' => $this->alias,
                'parameters' => $this->parameters,
            ],
            $this->query->getTokens(),
        );
    }

    public function getQuery(): SelectQuery
    {
        return $this->query;
    }

    public function __toString(): string
    {
        $parameters = new QueryParameters();

        return Interpolator::interpolate(
            $this->query->sqlStatement($parameters),
            $parameters->getParameters(),
        );
    }
}
