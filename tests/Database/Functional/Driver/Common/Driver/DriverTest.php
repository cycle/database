<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Driver;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class DriverTest extends BaseTest
{
    public function testTransactionLevel(): void
    {
        $this->assertSame(0, $this->database->getDriver()->getTransactionLevel());

        $this->database->begin();
        $this->assertSame(1, $this->database->getDriver()->getTransactionLevel());
        $this->database->begin();
        $this->assertSame(2, $this->database->getDriver()->getTransactionLevel());
        $this->database->begin();
        $this->assertSame(3, $this->database->getDriver()->getTransactionLevel());

        $this->database->rollback();
        $this->assertSame(2, $this->database->getDriver()->getTransactionLevel());
        $this->database->rollback();
        $this->assertSame(1, $this->database->getDriver()->getTransactionLevel());
        $this->database->rollback();
        $this->assertSame(0, $this->database->getDriver()->getTransactionLevel());
    }

    /**
     * @dataProvider datetimeDataProvider
     */
    public function testFormatDatetime(\DateTimeInterface $value): void
    {
        $original = clone $value;

        $driver = $this->database->getDriver();

        $ref = new \ReflectionMethod($driver, 'formatDatetime');
        $ref->setAccessible(true);

        $formatted = $ref->invokeArgs($driver, [$value]);
        $objectFromFormatted = new \DateTimeImmutable($formatted, $driver->getTimezone());

        // changed time in the new tz
        $this->assertSame('2000-01-22 16:23:45', $formatted);

        // timestamp not changed
        $this->assertSame($value->getTimestamp(), $objectFromFormatted->getTimestamp());

        // original timezone object not mutated
        $this->assertEquals($original->getTimezone(), $value->getTimezone());
    }

    public function datetimeDataProvider(): \Traversable
    {
        yield [new \DateTimeImmutable('2000-01-23T01:23:45.678+09:00')];
        yield [new \DateTime('2000-01-23T01:23:45.678+09:00')];
        yield [new class('2000-01-23T01:23:45.678+09:00') extends \DateTimeImmutable {}];
        yield [new class('2000-01-23T01:23:45.678+09:00') extends \DateTime {}];
    }
}
