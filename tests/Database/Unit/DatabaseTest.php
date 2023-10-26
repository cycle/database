<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit;

use Cycle\Database\Database;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\DriverInterface;
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

        $ref = new \ReflectionProperty($newDb, 'readDriver');
        $ref->setAccessible(true);

        $this->assertNull($ref->getValue($newDb));
        $this->assertNotSame($driver, $newDb->getDriver());
    }

    public function testWithoutCacheWithSameDriverAndReadDriver(): void
    {
        $driver = $this->createMock(Driver::class);

        $driver
            ->expects($this->once())
            ->method('withoutCache');

        $database = new Database('default', '', $driver, $driver);

        $newDb = $database->withoutCache();

        $refDriver = new \ReflectionProperty($newDb, 'driver');
        $refDriver->setAccessible(true);

        $refReadDriver = new \ReflectionProperty($newDb, 'readDriver');
        $refReadDriver->setAccessible(true);

        $this->assertSame($refDriver->getValue($newDb), $refReadDriver->getValue($newDb));
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

        $database = new Database('default', '', $driver, $readDriver);

        $newDb = $database->withoutCache();

        $refDriver = new \ReflectionProperty($newDb, 'driver');
        $refDriver->setAccessible(true);

        $refReadDriver = new \ReflectionProperty($newDb, 'readDriver');
        $refReadDriver->setAccessible(true);

        $this->assertNotSame($refDriver->getValue($newDb), $refReadDriver->getValue($newDb));
        $this->assertNotSame($driver, $refDriver->getValue($newDb));
        $this->assertNotSame($readDriver, $refReadDriver->getValue($newDb));
    }

    public function testWithoutCacheWithoutReadDriverAndWithoutMethod(): void
    {
        $driver = $this->createMock(DriverInterface::class);

        $database = new Database('default', '', $driver);

        $newDb = $database->withoutCache();

        $ref = new \ReflectionProperty($newDb, 'readDriver');
        $ref->setAccessible(true);

        $this->assertNull($ref->getValue($newDb));
        $this->assertSame($driver, $newDb->getDriver());
    }

    public function testWithoutCacheWithDriverAndReadDriverWithoutMethod(): void
    {
        $driver = $this->createMock(DriverInterface::class);
        $readDriver = $this->createMock(DriverInterface::class);

        $database = new Database('default', '', $driver, $readDriver);

        $newDb = $database->withoutCache();

        $refDriver = new \ReflectionProperty($newDb, 'driver');
        $refDriver->setAccessible(true);

        $refReadDriver = new \ReflectionProperty($newDb, 'readDriver');
        $refReadDriver->setAccessible(true);

        $this->assertNotSame($refDriver->getValue($newDb), $refReadDriver->getValue($newDb));
        $this->assertSame($driver, $refDriver->getValue($newDb));
        $this->assertSame($readDriver, $refReadDriver->getValue($newDb));
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

        $this->assertSame($refDriver->getValue($newDb), $refReadDriver->getValue($newDb));
        $this->assertSame($driver, $refDriver->getValue($newDb));
        $this->assertSame($driver, $refReadDriver->getValue($newDb));
    }
}
