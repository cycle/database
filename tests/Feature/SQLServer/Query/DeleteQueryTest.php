<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Query;

use Cycle\Database\Tests\Feature\Common\Query\DeleteQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Test;

final class DeleteQueryTest extends CommonClass
{
    #[Test]
    public function testDeleteWithWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND json_value({settings}, N'$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testDeleteWithOrWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR json_value({settings}, N'$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testDeleteWithWhereJsonNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_value({settings}, N'$.\"phone\".\"work\"') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_value({settings}, N'$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_value({settings}, N'$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND ? IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))");
        SqlAssert::sameParameters($select, [1, 'en']);
    }

    #[Test]
    public function testDeleteWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR ? IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))");
        SqlAssert::sameParameters($select, [1, 'en']);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\".\"work\"'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1]'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))");
        SqlAssert::sameParameters($select, [1, 'en']);
    }

    #[Test]
    public function testDeleteWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))");
        SqlAssert::sameParameters($select, [1, 'en']);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\".\"work\"'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1]'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND N'languages' IN (SELECT [key] FROM openjson({settings}))");
    }

    #[Test]
    public function testDeleteWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR N'languages' IN (SELECT [key] FROM openjson({settings}))");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "DELETE FROM {table} WHERE N'work' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE N'1' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE N'3' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"'))");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND NOT N'languages' IN (SELECT [key] FROM openjson({settings}))");
    }

    #[Test]
    public function testDeleteWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR NOT N'languages' IN (SELECT [key] FROM openjson({settings}))");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT N'work' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT N'1' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT N'3' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"'))");
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) >= ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND (SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) = ?");
        SqlAssert::sameParameters($select, [1, 3]);
    }

    #[Test]
    public function testDeleteWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR (SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) = ?");
        SqlAssert::sameParameters($select, [1, 4]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"personal\".\"languages\"')) = ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"phones\"[1]')) = ?");
        SqlAssert::sameParameters($select, [2]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "DELETE FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]')) = ?");
        SqlAssert::sameParameters($select, [5]);
    }
}
