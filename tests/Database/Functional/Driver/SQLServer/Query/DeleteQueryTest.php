<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\DeleteQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class DeleteQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testDeleteWithWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_value({settings}, '$.\"theme\"') = ?",
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
            "DELETE FROM {table} WHERE {id} = ? AND json_value({settings}, '$.\"theme\"') = ?",
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
            "DELETE FROM {table} WHERE {id} = ? OR json_value({settings}, '$.\"theme\"') = ?",
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
            "DELETE FROM {table} WHERE json_value({settings}, '$.\"phone\".\"work\"') = ?",
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
            "DELETE FROM {table} WHERE json_value({settings}, '$.\"phones\"[1]') = ?",
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
            "DELETE FROM {table} WHERE json_value({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
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
            "DELETE FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters(['en'], $select);
    }

    public function testDeleteWithAndWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->andWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND ? IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    public function testDeleteWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR ? IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    public function testDeleteWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, '$.\"phones\".\"work\"'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, '$.\"phones\"[1]'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, '$.\"phones\"[1].\"numbers\"[3]'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters(['en'], $select);
    }

    public function testDeleteWithAndWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->andWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    public function testDeleteWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    public function testDeleteWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"phones\".\"work\"'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"phones\"[1]'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testDeleteWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"phones\"[1].\"numbers\"[3]'))",
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
            "DELETE FROM {table} WHERE 'languages' IN (SELECT [key] FROM openjson({settings}))",
            $select
        );
    }

    public function testDeleteWithAndWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->andWhereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND 'languages' IN (SELECT [key] FROM openjson({settings}))",
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
            "DELETE FROM {table} WHERE {id} = ? OR 'languages' IN (SELECT [key] FROM openjson({settings}))",
            $select
        );
    }

    public function testDeleteWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones->work');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE 'work' IN (SELECT [key] FROM openjson({settings}, '$.\"phones\"'))",
            $select
        );
    }

    public function testDeleteWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE 1 IN (SELECT [key] FROM openjson({settings}, '$.\"phones\"'))",
            $select
        );
    }

    public function testDeleteWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE 3 IN (SELECT [key] FROM openjson({settings}, '$.\"phones\"[1].\"numbers\"'))",
            $select
        );
    }

    public function testDeleteWithWhereJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->languages', 1);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"languages\"')) = ?",
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
            "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"languages\"')) >= ?",
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
            "DELETE FROM {table} WHERE {id} = ? AND (SELECT count(*) FROM openjson({settings}, '$.\"languages\"')) = ?",
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
            "DELETE FROM {table} WHERE {id} = ? OR (SELECT count(*) FROM openjson({settings}, '$.\"languages\"')) = ?",
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
            "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"personal\".\"languages\"')) = ?",
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
            "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"phones\"[1]')) = ?",
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
            "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"phones\"[1].\"numbers\"[3]')) = ?",
            $select
        );
        $this->assertSameParameters([5], $select);
    }
}
