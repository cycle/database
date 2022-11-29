<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common;

use Cycle\Database\Config\ConnectionConfig;
use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Database;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\ActiveQuery;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\Comparator;
use Cycle\Database\Tests\Traits\Loggable;
use Cycle\Database\Tests\Traits\TableAssertions;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    use Loggable;
    use TableAssertions;

    public const DRIVER = null;

    public static array $config;
    protected Database $database;
    private static array $memoizedDrivers = [];

    public function setUp(): void
    {
        if (self::$config['debug'] ?? false) {
            $this->enableProfiling();
        }

        $this->database ??= $this->db();
    }

    public function tearDown(): void
    {
        $this->dropDatabase($this->database);
    }

    /**
     * @param array{readonly: bool} $driverConfig
     *
     * @return DriverInterface
     */
    private function getDriver(array $driverConfig = [], array $connectionConfig = []): DriverInterface
    {
        $hash = \hash('crc32', static::DRIVER . ':' . \json_encode($driverConfig) . \json_encode($connectionConfig));

        if (! isset(self::$memoizedDrivers[$hash])) {
            $config = clone self::$config[static::DRIVER];
            assert($config instanceof DriverConfig);

            $this->applyDriverOptions($config, $driverConfig);
            $config->connection = $this->applyConnectionOptions($config->connection, $connectionConfig);

            $driver = $config->driver::create($config);

            $this->setUpLogger($driver);

            self::$memoizedDrivers[$hash] = $driver;
        }

        return self::$memoizedDrivers[$hash];
    }

    private function applyConnectionOptions(ConnectionConfig $config, array $options): ConnectionConfig
    {
        if ($options === []) {
            return $config;
        }
        $config = clone $config;
        foreach ($options as $key => $value) {
            $config->$key = $value;
        }
        return $config;
    }

    private function applyDriverOptions(DriverConfig $config, array $options): void
    {
        // Add readonly options support
        if (isset($options['readonly']) && $options['readonly'] === true) {
            $config->readonly = true;
        }

        if (isset($options['datetimeWithMicroseconds']) && $options['datetimeWithMicroseconds'] === true) {
            $config->datetimeWithMicroseconds = true;
        }
    }

    /**
     * @param array{readonly: bool} $driverConfig
     * @param array $connectionConfig
     *
     * @return Database
     */
    protected function db(
        string $name = 'default',
        string $prefix = '',
        array $driverConfig = [],
        array $connectionConfig = []
    ): Database {
        return new Database($name, $prefix, $this->getDriver($driverConfig, $connectionConfig));
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
        if ($database === null) {
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

    protected function schema(string $table, string $prefix = '', array $driverConfig = []): AbstractTable
    {
        return $this->db('default', $prefix, $driverConfig)
            ->table($table)
            ->getSchema();
    }
}
