<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpdateQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class UpdateQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testUpdateWithWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery("UPDATE {table} SET {some} = ? WHERE {settings}->>'theme' = ?", $select);
        $this->assertSameParameters(['value', 'dark'], $select);
    }

    public function testUpdateWithAndWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->andWhereJson('settings->theme', 'dark');

        $this->assertSameQuery("UPDATE {table} SET {some} = ? WHERE {id} = ? AND {settings}->>'theme' = ?", $select);
        $this->assertSameParameters(['value', 1, 'dark'], $select);
    }

    public function testUpdateWithOrWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR {settings}->>'theme' = ?",
            $select
        );
        $this->assertSameParameters(['value', 1, 'dark'], $select);
    }

    public function testUpdateWithWhereJsonNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery("UPDATE {table} SET {some} = ? WHERE {settings}->'phone'->>'work' = ?", $select);
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    public function testUpdateWithWhereJsonArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery("UPDATE {table} SET {some} = ? WHERE {settings}->'phones'->>1 = ?", $select);
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    public function testUpdateWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {settings}->'phones'->1->'numbers'->>3 = ?",
            $select
        );
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }
}
