<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpdateQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class UpdateQueryTest extends CommonClass
{
    public const DRIVER = 'sqlite';


    public function testUpdateWithJsonWhere(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('settings->theme', 'dark');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_extract({settings}, '$.\"theme\"') = ?",
            $select
        );
        $this->assertSameParameters(['value', 'dark'], $select);
    }

    public function testUpdateWithNestedJsonWhere(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('settings->phone->work', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_extract({settings}, '$.\"phone\".\"work\"') = ?",
            $select
        );
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    public function testUpdateWithArrayJsonWhere(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1]')) = ?",
            $select
        );
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    public function testUpdateWithNestedArrayJsonWhere(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select
        );
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }
}
