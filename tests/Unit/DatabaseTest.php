<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Schema\AbstractTable;
use Testo\Assert;
use Testo\Test;

#[Test]
final class DatabaseTest
{
    public function testWithoutCacheWithoutReadDriver(): void
    {
        $driver = \Mockery::mock(Driver::class)->shouldIgnoreMissing();

        $driver->shouldReceive('withoutCache')->once()->andReturn(\Mockery::mock(Driver::class));

        $database = new Database('default', '', $driver);

        $newDb = $database->withoutCache();

        Assert::null($this->readProperty($newDb, 'readDriver'));
        Assert::same($database->getDriver(), $driver);
        Assert::notSame($newDb->getDriver(), $driver);
    }

    public function testWithoutCacheWithSameDriverAndReadDriver(): void
    {
        $driver = \Mockery::mock(Driver::class)->shouldIgnoreMissing();

        $driver->shouldReceive('withoutCache')->once()->andReturn(\Mockery::mock(Driver::class));

        $db = new Database('default', '', $driver, $driver);

        $newDb = $db->withoutCache();

        Assert::same($db->getDriver(DatabaseInterface::READ), $db->getDriver(DatabaseInterface::WRITE));
        Assert::same($newDb->getDriver(DatabaseInterface::READ), $newDb->getDriver(DatabaseInterface::WRITE));
    }

    public function testWithoutCacheWithDriverAndReadDriver(): void
    {
        $driver = \Mockery::mock(Driver::class)->shouldIgnoreMissing();
        $readDriver = \Mockery::mock(Driver::class)->shouldIgnoreMissing();

        $driver->shouldReceive('withoutCache')->once()->andReturn(\Mockery::mock(Driver::class));

        $readDriver->shouldReceive('withoutCache')->once()->andReturn(\Mockery::mock(Driver::class));

        $db = new Database('default', '', $driver, $readDriver);

        $newDb = $db->withoutCache();

        Assert::notSame($newDb->getDriver(DatabaseInterface::READ), $newDb->getDriver(DatabaseInterface::WRITE));
        Assert::notSame($newDb->getDriver(DatabaseInterface::WRITE), $driver);
        Assert::notSame($newDb->getDriver(DatabaseInterface::READ), $readDriver);
    }

    public function testWithoutCacheWithoutReadDriverAndWithoutMethod(): void
    {
        $driver = \Mockery::mock(DriverInterface::class)->shouldIgnoreMissing();
        $database = new Database('default', '', $driver);

        $newDb = $database->withoutCache();

        Assert::null($this->readProperty($newDb, 'readDriver'));
        Assert::same($newDb->getDriver(), $driver);
    }

    public function testWithoutCacheWithDriverAndReadDriverWithoutMethod(): void
    {
        $driver = \Mockery::mock(DriverInterface::class)->shouldIgnoreMissing();
        $readDriver = \Mockery::mock(DriverInterface::class)->shouldIgnoreMissing();
        $database = new Database('default', '', $driver, $readDriver);

        $newDb = $database->withoutCache();

        Assert::notSame($newDb->getDriver(DatabaseInterface::READ), $newDb->getDriver(DatabaseInterface::WRITE));
        Assert::same($newDb->getDriver(DatabaseInterface::WRITE), $driver);
        Assert::same($newDb->getDriver(DatabaseInterface::READ), $readDriver);
    }

    public function testWithoutCacheWithSameDriversAndWithoutMethod(): void
    {
        $driver = \Mockery::mock(DriverInterface::class)->shouldIgnoreMissing();

        $database = new Database('default', '', $driver, $driver);

        $newDb = $database->withoutCache();

        $refDriver = new \ReflectionProperty($newDb, 'driver');
        $refDriver->setAccessible(true);

        $refReadDriver = new \ReflectionProperty($newDb, 'readDriver');
        $refReadDriver->setAccessible(true);

        Assert::same($newDb->getDriver(DatabaseInterface::READ), $newDb->getDriver(DatabaseInterface::WRITE));
        Assert::same($newDb->getDriver(DatabaseInterface::WRITE), $driver);
        Assert::same($newDb->getDriver(DatabaseInterface::READ), $driver);
    }

    /**
     * When the driver's handler does not implement BulkSchemaProviderInterface, Database::getSchemas()
     * must fall back to a per-table getSchema() loop, applying the database prefix.
     */
    public function testGetSchemasFallsBackToPerTableWhenHandlerIsNotBulkProvider(): void
    {
        $tableA = \Mockery::mock(AbstractTable::class)->shouldIgnoreMissing();
        $tableB = \Mockery::mock(AbstractTable::class)->shouldIgnoreMissing();

        $handler = \Mockery::mock(HandlerInterface::class)->shouldIgnoreMissing();
        $handler->shouldReceive('getSchema')->andReturnUsing(static fn(...$args) => \array_column(\array_filter([
            ['a', 'pre_', $tableA],
            ['b', 'pre_', $tableB],
        ], static fn($row) => \array_slice($row, 0, -1) === $args), \count($args))[0] ?? null);

        $driver = \Mockery::mock(DriverInterface::class)->shouldIgnoreMissing();
        $driver->shouldReceive('getSchemaHandler')->andReturn($handler);

        $database = new Database('default', 'pre_', $driver);

        Assert::same($database->getSchemas(['a', 'b']), ['a' => $tableA, 'b' => $tableB]);
    }

    private function readProperty(object $object, string $property): mixed
    {
        $fn = function () use ($property) {
            return $this->$property;
        };

        return $fn->call($object);
    }
}
