<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Driver;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\Driver as DatabaseDriver;
use Cycle\Database\Exception\StatementException;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[OnlyDriver(Driver::SQLite)]
class DriverTest extends DatabaseTestCase
{
    #[Test]
    public function testTransactionLevel(): void
    {
        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);

        $this->database->begin();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
        $this->database->begin();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 2);
        $this->database->begin();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 3);

        $this->database->rollback();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 2);
        $this->database->rollback();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
        $this->database->rollback();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    #[Test]
    #[DataProvider('datetimeDataProvider')]
    public function testFormatDatetime(\DateTimeInterface $value): void
    {
        $original = clone $value;

        $driver = $this->database->getDriver();

        $ref = new \ReflectionMethod($driver, 'formatDatetime');
        $ref->setAccessible(true);

        $formatted = $ref->invokeArgs($driver, [$value]);
        $objectFromFormatted = new \DateTimeImmutable($formatted, $driver->getTimezone());

        // changed time in the new tz
        Assert::same($formatted, '2000-01-22 16:23:45');

        // timestamp not changed
        Assert::same($objectFromFormatted->getTimestamp(), $value->getTimestamp());

        // original timezone object not mutated
        Assert::equals($value->getTimezone(), $original->getTimezone());
    }

    public function datetimeDataProvider(): \Traversable
    {
        yield [new \DateTimeImmutable('2000-01-23T01:23:45.678+09:00')];
        yield [new \DateTime('2000-01-23T01:23:45.678+09:00')];
        yield [new class('2000-01-23T01:23:45.678+09:00') extends \DateTimeImmutable {}];
        yield [new class('2000-01-23T01:23:45.678+09:00') extends \DateTime {}];
    }

    #[Test]
    public function testClearCache(): void
    {
        $driver = $this->mockDriver();

        $driver->testPolluteCache();
        Assert::notBlank($driver->testGetCache());

        $driver->clearCache();

        Assert::blank($driver->testGetCache());
    }

    #[Test]
    public function testWithoutCache(): void
    {
        $driver = $this->mockDriver();
        $driver->testPolluteCache();
        Assert::notBlank($driver->testGetCache());

        $new = $driver->withoutCache();

        Assert::notBlank($driver->testGetCache());
        Assert::blank($new->testGetCache());
    }

    private function mockDriver(): DatabaseDriver
    {
        return new class extends DatabaseDriver {
            public function __construct() {}

            public function testPolluteCache(): void
            {
                $this->queryCache[] = ['sql' => 'SELECT * FROM table', 'params' => []];
            }

            public function testGetCache(): array
            {
                return $this->queryCache;
            }

            protected function mapException(\Throwable $exception, string $query): StatementException
            {
                throw new \Exception('not needed');
            }

            public static function create(DriverConfig $config): \Cycle\Database\Driver\DriverInterface
            {
                throw new \Exception('not needed');
            }

            public function getType(): string
            {
                throw new \Exception('not needed');
            }

            public function __clone(): void {}
        };
    }
}
