<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\DeleteQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class DeleteQueryTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testDeleteWithWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select
        );
        $this->assertSameParameters(['dark'], $select);
    }

    public function testDeleteWithAndWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->andWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select
        );
        $this->assertSameParameters([1, 'dark'], $select);
    }

    public function testDeleteWithOrWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select
        );
        $this->assertSameParameters([1, 'dark'], $select);
    }

    public function testDeleteWithWhereJsonNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phone\".\"work\"')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1]')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([json_encode('en')], $select);
    }

    public function testDeleteWithAndWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->andWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([1, json_encode('en')], $select);
    }

    public function testDeleteWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([1, json_encode('en')], $select);
    }

    public function testDeleteWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\".\"work\"')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testDeleteWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\"[1]')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testDeleteWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testDeleteWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([json_encode('en')], $select);
    }

    public function testDeleteWithAndWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->andWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([1, json_encode('en')], $select);
    }

    public function testDeleteWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([1, json_encode('en')], $select);
    }

    public function testDeleteWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\".\"work\"')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testDeleteWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1]')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testDeleteWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testDeleteWithWhereJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->languages', 1);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"languages\"') = ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testDeleteWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"languages\"') >= ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testDeleteWithAndJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->andWhereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND json_length({settings}, '$.\"languages\"') = ?",
            $select
        );
        $this->assertSameParameters([1, 3], $select);
    }

    public function testDeleteWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR json_length({settings}, '$.\"languages\"') = ?",
            $select
        );
        $this->assertSameParameters([1, 4], $select);
    }

    public function testDeleteWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->personal->languages', 1);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"personal\".\"languages\"') = ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testDeleteWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]', 2);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"phones\"[1]') = ?",
            $select
        );
        $this->assertSameParameters([2], $select);
    }

    public function testDeleteWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select
        );
        $this->assertSameParameters([5], $select);
    }
}
