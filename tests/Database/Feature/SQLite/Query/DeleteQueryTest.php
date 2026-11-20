<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Query;

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
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_extract({settings}, '$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, ['dark']);
    }

    #[Test]
    public function testDeleteWithOrWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR json_extract({settings}, '$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testDeleteWithWhereJsonNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_extract({settings}, '$.\"phone\".\"work\"') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_extract({settings}, '$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testDeleteWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_type({settings}, '$.\"phones\".\"work\"') IS NOT null");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_type({settings}, '$.\"phones\"[1]') IS NOT null");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_type({settings}, '$.\"phones\"[1].\"numbers\"[3]') IS NOT null");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testDeleteWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR NOT json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT json_type({settings}, '$.\"phones\".\"work\"') IS NOT null");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT json_type({settings}, '$.\"phones\"[1]') IS NOT null");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT json_type({settings}, '$.\"phones\"[1].\"numbers\"[3]') IS NOT null");
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_array_length({settings}, '$.\"languages\"') >= ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testDeleteWithJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND json_array_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1, 3]);
    }

    #[Test]
    public function testDeleteWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR json_array_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1, 4]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_array_length({settings}, '$.\"personal\".\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_array_length({settings}, '$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, [2]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "DELETE FROM {table} WHERE json_array_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, [5]);
    }
}
