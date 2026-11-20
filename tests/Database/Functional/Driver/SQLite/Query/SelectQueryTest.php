<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class SelectQueryTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    #[\Testo\Test]
    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM {users} LIMIT -1 OFFSET ?',
            $select,
        );

        $this->assertSameParameters(
            [
                20,
            ],
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectForUpdate(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Antony')
            ->forUpdate();

        $this->assertSameQuery(
            'SELECT * FROM {users} WHERE {name} = ?',
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery("SELECT * FROM {table} WHERE json_extract({settings}, '$.\"theme\"') = ?", $select);
        $this->assertSameParameters(['dark'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR json_extract({settings}, '$.\"theme\"') = ?",
            $select,
        );
        $this->assertSameParameters([1, 'dark'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_extract({settings}, '$.\"phone\".\"work\"') = ?",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE json_extract({settings}, '$.\"phones\"[1]') = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_type({settings}, '$.\"languages\"') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR json_type({settings}, '$.\"languages\"') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones->work');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_type({settings}, '$.\"phones\".\"work\"') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_type({settings}, '$.\"phones\"[1]') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_type({settings}, '$.\"phones\"[1].\"numbers\"[3]') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT json_type({settings}, '$.\"languages\"') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR NOT json_type({settings}, '$.\"languages\"') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT json_type({settings}, '$.\"phones\".\"work\"') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT json_type({settings}, '$.\"phones\"[1]') IS NOT null",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters([1], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"languages\"') >= ?",
            $select,
        );
        $this->assertSameParameters([1], $select);
    }

    #[\Testo\Test]
    public function testSelectWithJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND json_array_length({settings}, '$.\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters([1, 3], $select);
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR json_array_length({settings}, '$.\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters([1, 4], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->personal->languages', 1);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"personal\".\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters([1], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]', 2);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"phones\"[1]') = ?",
            $select,
        );
        $this->assertSameParameters([2], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_array_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select,
        );
        $this->assertSameParameters([5], $select);
    }

    #[\Testo\Test]
    public function testOrderByJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->orderBy('logs->created_at', 'DESC');

        $this->assertSameQuery(
            "SELECT * FROM {table} ORDER BY json_extract({logs}, '$.\"created_at\"') DESC",
            $select,
        );
    }
}
