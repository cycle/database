<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\DeleteQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class DeleteQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testDeleteWithJsonWhere(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('settings->theme', 'dark');

        $this->assertSameQuery("DELETE FROM {table} WHERE {settings}->>'theme' = ?", $select);
        $this->assertSameParameters(['dark'], $select);
    }

    public function testDeleteWithNestedJsonWhere(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('settings->phone->work', '+1234567890');

        $this->assertSameQuery("DELETE FROM {table} WHERE {settings}->'phone'->>'work' = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithArrayJsonWhere(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('settings->phones[1]', '+1234567890');

        $this->assertSameQuery("DELETE FROM {table} WHERE {settings}->'phones'->>1 = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithNestedArrayJsonWhere(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {settings}->'phones'->1->'numbers'->>3 = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }
}
