<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\DeleteQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class DeleteQueryTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    public function testDeleteWithWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_extract({settings}, '$.\"theme\"') = ?",
            $select
        );
        $this->assertSameParameters(['dark'], $select);
    }

    public function testDeleteWithOrWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR json_extract({settings}, '$.\"theme\"') = ?",
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
            "DELETE FROM {table} WHERE json_extract({settings}, '$.\"phone\".\"work\"') = ?",
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
            "DELETE FROM {table} WHERE json_extract({settings}, '$.\"phones\"[1]') = ?",
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
            "DELETE FROM {table} WHERE json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_type({settings}, '$.\"languages\"') IS NOT null",
            $select
        );
    }

    public function testDeleteWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR json_type({settings}, '$.\"languages\"') IS NOT null",
            $select
        );
    }

    public function testDeleteWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones->work');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_type({settings}, '$.\"phones\".\"work\"') IS NOT null",
            $select
        );
    }

    public function testDeleteWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_type({settings}, '$.\"phones\"[1]') IS NOT null",
            $select
        );
    }

    public function testDeleteWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_type({settings}, '$.\"phones\"[1].\"numbers\"[3]') IS NOT null",
            $select
        );
    }

    public function testDeleteWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_type({settings}, '$.\"languages\"') IS NOT null",
            $select
        );
    }

    public function testDeleteWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR NOT json_type({settings}, '$.\"languages\"') IS NOT null",
            $select
        );
    }

    public function testDeleteWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_type({settings}, '$.\"phones\".\"work\"') IS NOT null",
            $select
        );
    }

    public function testDeleteWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_type({settings}, '$.\"phones\"[1]') IS NOT null",
            $select
        );
    }

    public function testDeleteWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_type({settings}, '$.\"phones\"[1].\"numbers\"[3]') IS NOT null",
            $select
        );
    }

    public function testDeleteWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_array_length({settings}, '$.\"languages\"') >= ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testDeleteWithJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND json_array_length({settings}, '$.\"languages\"') = ?",
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
            "DELETE FROM {table} WHERE {id} = ? OR json_array_length({settings}, '$.\"languages\"') = ?",
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
            "DELETE FROM {table} WHERE json_array_length({settings}, '$.\"personal\".\"languages\"') = ?",
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
            "DELETE FROM {table} WHERE json_array_length({settings}, '$.\"phones\"[1]') = ?",
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
            "DELETE FROM {table} WHERE json_array_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select
        );
        $this->assertSameParameters([5], $select);
    }
}
