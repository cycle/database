<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Driver;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class DriverTest extends BaseTest
{
    #[\Testo\Test]
    public function testTransactionLevel(): void
    {
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);

        $this->database->begin();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
        $this->database->begin();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 2);
        $this->database->begin();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 3);

        $this->database->rollback();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 2);
        $this->database->rollback();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);
        $this->database->rollback();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('datetimeDataProvider')]
    public function testFormatDatetime(\DateTimeInterface $value): void
    {
        $original = clone $value;

        $driver = $this->database->getDriver();

        $ref = new \ReflectionMethod($driver, 'formatDatetime');
        $ref->setAccessible(true);

        $formatted = $ref->invokeArgs($driver, [$value]);
        $objectFromFormatted = new \DateTimeImmutable($formatted, $driver->getTimezone());

        // changed time in the new tz
        \Testo\Assert::same($formatted, '2000-01-22 16:23:45');

        // timestamp not changed
        \Testo\Assert::same($objectFromFormatted->getTimestamp(), $value->getTimestamp());

        // original timezone object not mutated
        \Testo\Assert::equals($value->getTimezone(), $original->getTimezone());
    }

    public function datetimeDataProvider(): \Traversable
    {
        yield [new \DateTimeImmutable('2000-01-23T01:23:45.678+09:00')];
        yield [new \DateTime('2000-01-23T01:23:45.678+09:00')];
        yield [new class('2000-01-23T01:23:45.678+09:00') extends \DateTimeImmutable {}];
        yield [new class('2000-01-23T01:23:45.678+09:00') extends \DateTime {}];
    }

    #[\Testo\Test]
    public function testClearCache(): void
    {
        $driver = $this->mockDriver();

        $driver->testPolluteCache();
        self::assertNotEmpty($driver->testGetCache());

        $driver->clearCache();

        self::assertEmpty($driver->testGetCache());
    }

    #[\Testo\Test]
    public function testCursorThrowsByDefault(): void
    {
        if (\in_array(static::DRIVER, ['postgres', 'sqlite', 'sqlserver'], true)) {
            throw new \Testo\Core\Exception\SkipTest(\sprintf('Driver `%s` implements CursorInterface.', static::DRIVER));
        }

        \Testo\Expect::exception(DriverException::class)->withMessageMatchingRegex('/cursors are not supported/i');

        // Database::cursor() checks CursorInterface and throws for drivers that don't implement it.
        \iterator_to_array(
            $this->database->cursor($this->database->select('1')),
        );
    }

    #[\Testo\Test]
    public function testWithoutCache(): void
    {
        $driver = $this->mockDriver();
        $driver->testPolluteCache();
        self::assertNotEmpty($driver->testGetCache());

        $new = $driver->withoutCache();

        self::assertNotEmpty($driver->testGetCache());
        self::assertEmpty($new->testGetCache());
    }

    private function mockDriver(): Driver
    {
        return new class extends Driver {
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
