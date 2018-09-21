<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Query;

use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Driver\DriverInterface;

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
     * @param DriverInterface   $driver   Associated driver.
     * @param CompilerInterface $compiler Driver specific QueryCompiler instance (one per builder).
     */
    public function __construct(DriverInterface $driver, CompilerInterface $compiler)
    {
        $this->driver = $driver;
        $this->compiler = $compiler;
    }

    /**
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Get interpolated (populated with parameters) SQL which will be run against database, please
     * use this method for debug purposes only.
     *
     * @return string
     */
    public function queryString(): string
    {
        return Interpolator::interpolate($this->sqlStatement(), $this->getParameters());
    }

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
        try {
            $queryString = $this->queryString();
        } catch (\Exception $e) {
            $queryString = "[ERROR: {$e->getMessage()}]";
        }

        $debugInfo = [
            'statement'  => $queryString,
            'parameters' => $this->getParameters(),
            'driver'     => $this->driver
        ];

        return $debugInfo;
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

    /**
     * Expand all QueryBuilder parameters to create flatten list.
     *
     * @param array $parameters
     *
     * @return array
     */
    protected function flattenParameters(array $parameters): array
    {
        $result = [];
        foreach ($parameters as $parameter) {
            if ($parameter instanceof BuilderInterface) {
                $result = array_merge($result, $parameter->getParameters());
                continue;
            }

            $result[] = $parameter;
        }

        return $result;
    }
}
