<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Builders;

use Interop\Container\ContainerInterface;
use Spiral\Core\Component;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Entities\QueryCompiler;
use Spiral\Database\Exceptions\BuilderException;
use Spiral\Database\Helpers\QueryInterpolator;
use Spiral\Database\Injections\ExpressionInterface;
use Spiral\Database\Injections\ParameterInterface;

/**
 * QueryBuilder classes generate set of control tokens for query compilers, this is query level
 * abstraction.
 */
abstract class QueryBuilder extends Component implements ExpressionInterface
{
    /**
     * @var Driver
     */
    protected $driver = null;

    /**
     * @var QueryCompiler
     */
    protected $compiler = null;

    /**
     * @param Driver        $driver   Associated driver.
     * @param QueryCompiler $compiler Driver specific QueryCompiler instance (one per builder).
     */
    public function __construct(Driver $driver, QueryCompiler $compiler)
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
     * @param QueryCompiler $compiler Associated compiled to be used by default.
     */
    abstract public function sqlStatement(QueryCompiler $compiler = null): string;

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
     * use this method for debugging purposes only.
     *
     * @return string
     */
    public function queryString(): string
    {
        return QueryInterpolator::interpolate($this->sqlStatement(), $this->getParameters());
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
     * parameters.
     * It support array list, string or comma separated list. Attention, this method will not work
     * with complex parameters (such as functions) provided as one comma separated string, please
     * use arrays in this case.
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
            if ($parameter instanceof self) {
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

    /**
     * @return ContainerInterface
     */
    protected function iocContainer()
    {
        //Falling back to driver specific container
        return $this->driver->iocContainer();
    }
}
