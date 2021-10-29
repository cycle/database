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
use Throwable;
use Spiral\Database\Driver\DriverInterface as SpiralDriverInterface;
use Spiral\Database\Query\QueryParameters as SpiralQueryParameters;

interface_exists(SpiralDriverInterface::class);
class_exists(SpiralQueryParameters::class);

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
    public function withDriver(SpiralDriverInterface $driver, string $prefix = null): QueryInterface
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
    public function sqlStatement(SpiralQueryParameters $parameters = null): string
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
