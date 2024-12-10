<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\StatementException;

/**
 * QueryBuilder classes generate set of control tokens for query compilers, this is query level
 * abstraction.
 *
 * @internal
 */
abstract class ActiveQuery implements QueryInterface, \Stringable
{
    protected ?DriverInterface $driver = null;
    protected ?string $prefix = null;

    public function withDriver(DriverInterface $driver, ?string $prefix = null): QueryInterface
    {
        $query = clone $this;
        $query->driver = $driver;
        $query->prefix = $prefix;

        return $query;
    }

    public function getDriver(): ?DriverInterface
    {
        return $this->driver;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Generate SQL query, must have associated driver instance.
     *
     * @psalm-return non-empty-string
     */
    public function sqlStatement(?QueryParameters $parameters = null): string
    {
        $this->driver === null and throw new BuilderException('Unable to build query without associated driver');

        return $this->driver->getQueryCompiler()->compile(
            $parameters ?? new QueryParameters(),
            $this->prefix,
            $this,
        );
    }

    /**
     * Compile and run query.
     *
     * @throws BuilderException
     * @throws StatementException
     */
    abstract public function run(): mixed;

    public function __toString(): string
    {
        $parameters = new QueryParameters();

        return Interpolator::interpolate(
            $this->sqlStatement($parameters),
            $parameters->getParameters(),
        );
    }

    public function __debugInfo(): array
    {
        $parameters = new QueryParameters();

        try {
            $queryString = $this->sqlStatement($parameters);
        } catch (\Throwable $e) {
            $queryString = "[ERROR: {$e->getMessage()}]";
        }

        return [
            'queryString' => Interpolator::interpolate($queryString, $parameters->getParameters()),
            'parameters'  => $parameters->getParameters(),
            'driver'      => $this->driver,
        ];
    }

    /**
     * Helper methods used to correctly fetch and split identifiers provided by function
     * parameters. Example: fI(['name, email']) => 'name', 'email'
     */
    protected function fetchIdentifiers(array $identifiers): array
    {
        if (\count($identifiers) === 1 && \is_string($identifiers[0])) {
            return \array_map('trim', \explode(',', $identifiers[0]));
        }

        if (\count($identifiers) === 1 && \is_array($identifiers[0])) {
            return $identifiers[0];
        }

        return $identifiers;
    }
}
