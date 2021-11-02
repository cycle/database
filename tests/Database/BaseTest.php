<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Tests\Traits\Loggable;
use Cycle\Database\Tests\Traits\TableAssertions;
use PHPUnit\Framework\TestCase;
use Cycle\Database\Database;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Query\ActiveQuery;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\Comparator;

abstract class BaseTest extends TestCase
{
    use TableAssertions;
    use Loggable;

    /**
     * @var string|null
     */
    public const DRIVER = null;

    /**
     * @var array
     */
    public static array $config;

    /**
     * @var Database
     */
    protected Database $database;

    /**
     * @var array<string, Database>
     */
    private static array $memoizedDrivers = [];

    public function setUp(): void
    {
        if (self::$config['debug'] ?? false) {
            $this->enableProfiling();
        }

        $this->database = $this->db();
    }

    public function tearDown(): void
    {
        $this->dropDatabase($this->database);
    }

    /**
     * @param array{readonly: bool} $options
     * @return DriverInterface
     */
    private function getDriver(array $options = []): DriverInterface
    {
        $hash = \hash('crc32', static::DRIVER . ':' . \json_encode($options));

        if (! isset(self::$memoizedDrivers[$hash])) {
            /** @var DriverConfig $config */
            $config = clone self::$config[static::DRIVER];

            // Add readonly options support
            if (isset($options['readonly']) && $options['readonly'] === true) {
                $config->readonly = true;
            }

            $driver = $config->driver::create($config);

            $this->setUpLogger($driver);

            self::$memoizedDrivers[$hash] = $driver;
        }

        return self::$memoizedDrivers[$hash];
    }

    /**
     * @param string $name
     * @param string $prefix
     * @param array{readonly: bool} $config
     * @return Database
     */
    protected function db(string $name = 'default', string $prefix = '', array $config = []): Database
    {
        return new Database($name, $prefix, $this->getDriver($config));
    }

    /**
     * Send sample query in a form where all quotation symbols replaced with { and }.
     *
     * @param string                   $query
     * @param string|FragmentInterface $fragment
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

            return "Table '{$table}' not synced, column(s) '" . join(
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
