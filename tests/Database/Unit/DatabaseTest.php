<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Schema\AbstractTable;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    public function testWithoutCacheWithoutReadDriver(): void
    {
        $driver = $this->createMock(Driver::class);

        $driver
            ->expects($this->once())
            ->method('withoutCache');

        $database = new Database('default', '', $driver);

        $newDb = $database->withoutCache();

        $this->assertNull($this->readProperty($newDb, 'readDriver'));
        $this->assertsame($driver, $database->getDriver());
        $this->assertNotSame($driver, $newDb->getDriver());
    }

    public function testWithoutCacheWithSameDriverAndReadDriver(): void
    {
        $driver = $this->createMock(Driver::class);

        $driver
            ->expects($this->once())
            ->method('withoutCache');

        $db = new Database('default', '', $driver, $driver);

        $newDb = $db->withoutCache();

        $this->assertSame($db->getDriver(DatabaseInterface::WRITE), $db->getDriver(DatabaseInterface::READ));
        $this->assertSame($newDb->getDriver(DatabaseInterface::WRITE), $newDb->getDriver(DatabaseInterface::READ));
    }

    public function testWithoutCacheWithDriverAndReadDriver(): void
    {
        $driver = $this->createMock(Driver::class);
        $readDriver = $this->createMock(Driver::class);

        $driver
            ->expects($this->once())
            ->method('withoutCache');

        $readDriver
            ->expects($this->once())
            ->method('withoutCache');

        $db = new Database('default', '', $driver, $readDriver);

        $newDb = $db->withoutCache();

        $this->assertNotSame($newDb->getDriver(DatabaseInterface::WRITE), $newDb->getDriver(DatabaseInterface::READ));
        $this->assertNotSame($driver, $newDb->getDriver(DatabaseInterface::WRITE));
        $this->assertNotSame($readDriver, $newDb->getDriver(DatabaseInterface::READ));
    }

    public function testWithoutCacheWithoutReadDriverAndWithoutMethod(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $database = new Database('default', '', $driver);

        $newDb = $database->withoutCache();

        $this->assertNull($this->readProperty($newDb, 'readDriver'));
        $this->assertSame($driver, $newDb->getDriver());
    }

    public function testWithoutCacheWithDriverAndReadDriverWithoutMethod(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $readDriver = $this->createMock(DriverInterface::class);
        $database = new Database('default', '', $driver, $readDriver);

        $newDb = $database->withoutCache();

        $this->assertNotSame($newDb->getDriver(DatabaseInterface::WRITE), $newDb->getDriver(DatabaseInterface::READ));
        $this->assertSame($driver, $newDb->getDriver(DatabaseInterface::WRITE));
        $this->assertSame($readDriver, $newDb->getDriver(DatabaseInterface::READ));
    }

    public function testWithoutCacheWithSameDriversAndWithoutMethod(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $database = new Database('default', '', $driver, $driver);

        $newDb = $database->withoutCache();

        $refDriver = new \ReflectionProperty($newDb, 'driver');
        $refDriver->setAccessible(true);

        $refReadDriver = new \ReflectionProperty($newDb, 'readDriver');
        $refReadDriver->setAccessible(true);

        $this->assertSame($newDb->getDriver(DatabaseInterface::WRITE), $newDb->getDriver(DatabaseInterface::READ));
        $this->assertSame($driver, $newDb->getDriver(DatabaseInterface::WRITE));
        $this->assertSame($driver, $newDb->getDriver(DatabaseInterface::READ));
    }

    /**
     * When the driver's handler does not implement BulkSchemaProviderInterface, Database::getSchemas()
     * must fall back to a per-table getSchema() loop, applying the database prefix.
     */
    public function testGetSchemasFallsBackToPerTableWhenHandlerIsNotBulkProvider(): void
    {
        $tableA = $this->createMock(AbstractTable::class);
        $tableB = $this->createMock(AbstractTable::class);

        $handler = $this->createMock(HandlerInterface::class);
        $handler->method('getSchema')->willReturnMap([
            ['a', 'pre_', $tableA],
            ['b', 'pre_', $tableB],
        ]);

        $driver = $this->createMock(DriverInterface::class);
        $driver->method('getSchemaHandler')->willReturn($handler);

        $database = new Database('default', 'pre_', $driver);

        $this->assertSame(['a' => $tableA, 'b' => $tableB], $database->getSchemas(['a', 'b']));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $fn = function () use ($property) {
            return $this->$property;
        };

        return $fn->call($object);
    }
}
