<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Query;

use Cycle\Database\Tests\Feature\Common\Query\UpdateQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Test;

final class UpdateQueryTest extends CommonClass
{
    #[Test]
    public function testUpdateWithWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_extract({settings}, '$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, ['value', 'dark']);
    }

    #[Test]
    public function testUpdateWithOrWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR json_extract({settings}, '$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, ['value', 1, 'dark']);
    }

    #[Test]
    public function testUpdateWithWhereJsonNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_extract({settings}, '$.\"phone\".\"work\"') = ?");
        SqlAssert::sameParameters($select, ['value', '+1234567890']);
    }

    #[Test]
    public function testUpdateWithWhereJsonArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_extract({settings}, '$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, ['value', '+1234567890']);
    }

    #[Test]
    public function testUpdateWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, ['value', '+1234567890']);
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testUpdateWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_type({settings}, '$.\"phones\".\"work\"') IS NOT null");
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_type({settings}, '$.\"phones\"[1]') IS NOT null");
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_type({settings}, '$.\"phones\"[1].\"numbers\"[3]') IS NOT null");
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testUpdateWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR NOT json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT json_type({settings}, '$.\"phones\".\"work\"') IS NOT null");
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT json_type({settings}, '$.\"phones\"[1]') IS NOT null");
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT json_type({settings}, '$.\"phones\"[1].\"numbers\"[3]') IS NOT null");
    }

    #[Test]
    public function testUpdateWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_array_length({settings}, '$.\"languages\"') >= ?");
        SqlAssert::sameParameters($select, ['value', 1]);
    }

    #[Test]
    public function testUpdateWithJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? AND json_array_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, ['value', 1, 3]);
    }

    #[Test]
    public function testUpdateWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR json_array_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, ['value', 1, 4]);
    }

    #[Test]
    public function testUpdateWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_array_length({settings}, '$.\"personal\".\"languages\"') = ?");
        SqlAssert::sameParameters($select, ['value', 1]);
    }

    #[Test]
    public function testUpdateWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_array_length({settings}, '$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, ['value', 2]);
    }

    #[Test]
    public function testUpdateWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE json_array_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, ['value', 5]);
    }
}
