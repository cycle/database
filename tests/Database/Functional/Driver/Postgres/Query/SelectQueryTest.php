<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class SelectQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testJsonWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->>'theme' = ?", $select);
        $this->assertSameParameters(['dark'], $select);
    }

    public function testNestedJsonWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phone'->>'work' = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testArrayJsonWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phones'->>1 = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testNestedArrayJsonWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phones'->1->'numbers'->>3 = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }
}
