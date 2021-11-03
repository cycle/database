<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests;

use Cycle\Database\Tests\Traits\Loggable;
use Cycle\Database\Tests\Traits\TableAssertions;
use PHPUnit\Framework\TestCase;
use Cycle\Database\Database;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\ActiveQuery;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\Comparator;

abstract class BaseTest extends TestCase
{
    use Loggable;
    use TableAssertions;

    public const DRIVER = null;

    /** @var array */
    public static $config;

    /** @var array */
    public static $driverCache = [];

    /** @var Driver */
    protected $driver;

    /** @var Database */
    protected $database;

    public function setUp(): void
    {
        $this->database = $this->db();
    }

    /**
     * @param array $options
     * @return Driver
     */
    public function getDriver(array $options = []): Driver
    {
        $config = self::$config[static::DRIVER];

        if (!isset($this->driver)) {
            $class = $config['driver'];

            $options = \array_merge($options, [
                'connection' => $config['conn'],
                'username' => $config['user'] ?? '',
                'password' => $config['pass'] ?? '',
                'options' => [],
                'queryCache' => true,
            ]);

            if (isset($config['schema'])) {
                $options['schema'] = $config['schema'];
            }

            $this->driver = new $class($options);
        }

        $this->setUpLogger($this->driver);

        if (self::$config['debug']) {
            $this->enableProfiling();
        }

        return $this->driver;
    }

    /**
     * @param string $name
     * @param string $prefix
     * @param array $config
     *
     * @return Database|null When non empty null will be given, for safety, for science.
     */
    protected function db(string $name = 'default', string $prefix = '', array $config = []): ?Database
    {
        if (isset(static::$driverCache[static::DRIVER])) {
            $driver = static::$driverCache[static::DRIVER];
        } else {
            static::$driverCache[static::DRIVER] = $driver = $this->getDriver($config);
        }

        return new Database($name, $prefix, $driver);
    }

    /**
     * Send sample query in a form where all quotation symbols replaced with { and }.
     *
     * @param string                   $query
     * @param string                   $parameters
     * @param FragmentInterface|string $fragment
     */
    protected function assertSameQueryWithParameters(string $query, array $parameters, $fragment): void
    {
        $this->assertSameQuery($query, $fragment);
        $this->assertSameParameters($parameters, $fragment);
    }

    /**
     * Send sample query in a form where all quotation symbols replaced with { and }.
     *
     * @param string                   $query
     * @param FragmentInterface|string $fragment
     */
    protected function assertSameQuery(string $query, $fragment): void
    {
        if ($fragment instanceof ActiveQuery) {
            $fragment = $fragment->sqlStatement();
        }

        //Preparing query
        $query = str_replace(
            ['{', '}'],
            explode('\a', $this->db()->getDriver()->identifier('\a')),
            $query
        );

        $this->assertSame(
            preg_replace('/\s+/', '', $query),
            preg_replace('/\s+/', '', (string)$fragment)
        );
    }

    /**
     * @param Database|null $database
     */
    protected function dropDatabase(Database $database = null): void
    {
        if ($database == null) {
            return;
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();

            foreach ($schema->getForeignKeys() as $foreign) {
                $schema->dropForeignKey($foreign->getColumns());
            }

            $schema->save(Handler::DROP_FOREIGN_KEYS);
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

    /**
     * @param AbstractTable $table
     *
     * @return AbstractTable
     */
    protected function fetchSchema(AbstractTable $table): AbstractTable
    {
        return $this->schema($table->getFullName());
    }

    protected function makeMessage(string $table, Comparator $comparator)
    {
        if ($comparator->isPrimaryChanged()) {
            return "Table '{$table}' not synced, primary indexes are different.";
        }

        if ($comparator->droppedColumns()) {
            return "Table '{$table}' not synced, columns are missing.";
        }

        if ($comparator->addedColumns()) {
            return "Table '{$table}' not synced, new columns found.";
        }

        if ($comparator->alteredColumns()) {
            $names = [];
            foreach ($comparator->alteredColumns() as $pair) {
                $names[] = $pair[0]->getName();
                print_r($pair);
            }

            return "Table '{$table}' not synced, column(s) '" . implode(
                "', '",
                $names
            ) . "' have been changed.";
        }

        if ($comparator->droppedForeignKeys()) {
            return "Table '{$table}' not synced, FKs are missing.";
        }

        if ($comparator->addedForeignKeys()) {
            return "Table '{$table}' not synced, new FKs found.";
        }


        return "Table '{$table}' not synced, no idea why, add more messages :P";
    }

    protected function assertSameParameters(array $parameters, ActiveQuery $query): void
    {
        $queryParams = new QueryParameters();
        $query->sqlStatement($queryParams);

        $builderParameters = [];
        foreach ($queryParams->getParameters() as $param) {
            if ($param instanceof ParameterInterface) {
                $param = $param->getValue();
            }

            $builderParameters[] = $param;
        }

        $this->assertEquals($parameters, $builderParameters);
    }
}
