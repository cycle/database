<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Exception\StatementException;
use Throwable;

/**
 * QueryBuilder classes generate set of control tokens for query compilers, this is query level
 * abstraction.
 */
abstract class ActiveQuery implements QueryInterface
{
    /** @var DriverInterface */
    protected $driver;

    /** @var string|null */
    protected $prefix;

    /**
     * @return string
     */
    public function __toString()
    {
        $parameters = new QueryParameters();

        return Interpolator::interpolate(
            $this->sqlStatement($parameters),
            $parameters->getParameters()
        );
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $parameters = new QueryParameters();

        try {
            $queryString = $this->sqlStatement($parameters);
        } catch (Throwable $e) {
            $queryString = "[ERROR: {$e->getMessage()}]";
        }

        return [
            'queryString' => Interpolator::interpolate($queryString, $parameters->getParameters()),
            'parameters'  => $parameters->getParameters(),
            'driver'      => $this->driver
        ];
    }

    /**
     * @param DriverInterface $driver
     * @param string|null     $prefix
     * @return QueryInterface|$this
     */
    public function withDriver(DriverInterface $driver, string $prefix = null): QueryInterface
    {
        $query = clone $this;
        $query->driver = $driver;
        $query->prefix = $prefix;

        return $query;
    }

    /**
     * @return DriverInterface|null
     */
    public function getDriver(): ?DriverInterface
    {
        return $this->driver;
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Generate SQL query, must have associated driver instance.
     *
     * @param QueryParameters|null $parameters
     * @return string
     */
    public function sqlStatement(QueryParameters $parameters = null): string
    {
        if ($this->driver === null) {
            throw new BuilderException('Unable to build query without associated driver');
        }

        return $this->driver->getQueryCompiler()->compile(
            $parameters ?? new QueryParameters(),
            $this->prefix,
            $this
        );
    }

    /**
     * Compile and run query.
     *
     * @return mixed
     *
     * @throws BuilderException
     * @throws StatementException
     */
    abstract public function run();

    /**
     * Helper methods used to correctly fetch and split identifiers provided by function
     * parameters. Example: fI(['name, email']) => 'name', 'email'
     *
     * @param array $identifiers
     *
     * @return array
     */
    protected function fetchIdentifiers(array $identifiers): array
    {
        if (count($identifiers) === 1 && is_string($identifiers[0])) {
            return array_map('trim', explode(',', $identifiers[0]));
        }

        if (count($identifiers) === 1 && is_array($identifiers[0])) {
            return $identifiers[0];
        }

        return $identifiers;
    }
}
