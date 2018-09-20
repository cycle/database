<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Query;

use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Injection\ExpressionInterface;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\QueryInterface;

/**
 * QueryBuilder classes generate set of control tokens for query compilers, this is query level
 * abstraction.
 */
abstract class QueryBuilder implements ExpressionInterface, QueryInterface
{
    /**
     * @var Driver
     */
    protected $driver = null;

    /**
     * @var Compiler
     */
    protected $compiler = null;

    /**
     * @param Driver   $driver   Associated driver.
     * @param Compiler $compiler Driver specific QueryCompiler instance (one per builder).
     */
    public function __construct(Driver $driver, Compiler $compiler)
    {
        $this->driver = $driver;
        $this->compiler = $compiler;
    }

    /**
     * @return Driver
     */
    public function getDriver(): Driver
    {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     *
     * @param Compiler $quoter Associated compiler to be used by default.
     */
    abstract public function sqlStatement(Compiler $quoter = null): string;

    /**
     * Get ordered list of builder parameters in a form of ParameterInterface array.
     *
     * @return ParameterInterface[]
     *
     * @throws BuilderException
     */
    abstract public function getParameters(): array;

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
            if ($parameter instanceof QueryBuilder) {
                $result = array_merge($result, $parameter->getParameters());
                continue;
            }

            $result[] = $parameter;
        }

        return $result;
    }

    /**
     * Generate PDO statement based on generated sql and parameters.
     *
     * @return \PDOStatement
     */
    protected function pdoStatement(): \PDOStatement
    {
        return $this->driver->statement($this->sqlStatement(), $this->getParameters());
    }
}
