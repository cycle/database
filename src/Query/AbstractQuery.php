<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Driver\QueryBindings;
use Spiral\Database\Exception\BuilderException;

/**
 * QueryBuilder classes generate set of control tokens for query compilers, this is query level
 * abstraction.
 */
abstract class AbstractQuery implements BuilderInterface
{
    /** @var DriverInterface */
    protected $driver = null;

    /** @var CompilerInterface */
    protected $compiler = null;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->sqlStatement();
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $bindings = new QueryBindings();

        try {
            $queryString = $this->compile($bindings, $this->compiler);
            ;
        } catch (\Exception $e) {
            $queryString = "[ERROR: {$e->getMessage()}]";
        }

        $debugInfo = [
            'statement' => $queryString,
            'bindings'  => $bindings->getParameters(),
            'driver'    => $this->driver
        ];

        return $debugInfo;
    }

    /**
     * @return DriverInterface|null
     */
    public function getDriver(): ?DriverInterface
    {
        return $this->driver;
    }

    /**
     * @return CompilerInterface|null
     */
    public function getCompiler(): ?CompilerInterface
    {
        return $this->compiler;
    }

    /**
     * @param DriverInterface        $driver
     * @param CompilerInterface|null $compiler
     * @return static
     */
    public function withDriver(DriverInterface $driver, CompilerInterface $compiler = null)
    {
        $query = clone $this;
        $query->driver = $driver;
        $query->compiler = $compiler ?? $driver->getCompiler();

        return $query;
    }

    /**
     * Get interpolated (populated with parameters) SQL which will be run against database, please
     * use this method for debug purposes only.
     *
     * @return string
     */
    public function sqlStatement(): string
    {
        if ($this->compiler === null) {
            throw new BuilderException('Unable to build query without associated driver');
        }

        return $this->compile(new QueryBindings(), $this->compiler);
    }

    /**
     * Get query parameters.
     *
     * @return array
     */
    public function getParameters(): array
    {
        if ($this->compiler === null) {
            throw new BuilderException('Unable to build query without associated driver');
        }

        $bindings = new QueryBindings();
        $this->compile($bindings, $this->compiler);

        return $bindings->getParameters();
    }

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
        if (count($identifiers) == 1 && is_string($identifiers[0])) {
            return array_map('trim', explode(',', $identifiers[0]));
        }

        if (count($identifiers) == 1 && is_array($identifiers[0])) {
            return $identifiers[0];
        }

        return $identifiers;
    }
}
