<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Query;

use Cycle\Database\Tests\Feature\Common\Query\SelectQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Test;

final class SelectQueryTest extends CommonClass
{
    #[Test]
    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        SqlAssert::same($select, 'SELECT * FROM {users} LIMIT -1 OFFSET ?');

        SqlAssert::sameParameters($select, [
            20,
        ]);
    }

    #[Test]
    public function testSelectForUpdate(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Antony')
            ->forUpdate();

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_extract({settings}, '$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, ['dark']);
    }

    #[Test]
    public function testSelectWithOrWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR json_extract({settings}, '$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_extract({settings}, '$.\"phone\".\"work\"') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_extract({settings}, '$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testSelectWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_type({settings}, '$.\"phones\".\"work\"') IS NOT null");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_type({settings}, '$.\"phones\"[1]') IS NOT null");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_type({settings}, '$.\"phones\"[1].\"numbers\"[3]') IS NOT null");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testSelectWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR NOT json_type({settings}, '$.\"languages\"') IS NOT null");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT json_type({settings}, '$.\"phones\".\"work\"') IS NOT null");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT json_type({settings}, '$.\"phones\"[1]') IS NOT null");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"languages\"') >= ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND json_array_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1, 3]);
    }

    #[Test]
    public function testSelectWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR json_array_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1, 4]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"personal\".\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, [2]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, [5]);
    }

    #[Test]
    public function testOrderByJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->orderBy('logs->created_at', 'DESC');

        SqlAssert::same($select, "SELECT * FROM {table} ORDER BY json_extract({logs}, '$.\"created_at\"') DESC");
    }
}
