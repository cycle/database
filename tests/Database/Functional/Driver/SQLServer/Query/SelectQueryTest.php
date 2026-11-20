<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class SelectQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    //FALLBACK!
    #[\Testo\Test]
    public function testLimitNoOffset(): void
    {
        $select = $this->database->select()->from(['users'])->limit(10);

        $this->assertSameQuery(
            'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} BETWEEN ? AND ?',
            $select,
        );

        $this->assertSameParameters(
            [
                1,
                10,
            ],
            $select,
        );
    }

    //FALLBACK!
    #[\Testo\Test]
    public function testLimitAndOffset(): void
    {
        $select = $this->database->select()->from(['users'])->limit(10)->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} BETWEEN ? AND ?',
            $select,
        );

        $this->assertSameParameters(
            [
                21,
                30,
            ],
            $select,
        );
    }

    //NO FALLBACK
    #[\Testo\Test]
    public function testLimitAndOffsetAndOrderBy(): void
    {
        $select = $this->database->select()->from(['users'])
            ->limit(10)
            ->orderBy('name')
            ->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM {users} ORDER BY {name} ASC OFFSET ? ROWS FETCH FIRST ? ROWS ONLY',
            $select,
        );


        $this->assertSameParameters(
            [
                20,
                10,
            ],
            $select,
        );
    }

    //FALLBACK!
    #[\Testo\Test]
    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} >= ?',
            $select,
        );

        $this->assertSameParameters(
            [
                21,
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
            'SELECT * FROM {users} WITH(UPDLOCK,ROWLOCK) WHERE {name} = ?',
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND json_value({settings}, N'$.\"theme\"') = ?",
            $select,
        );
        $this->assertSameParameters([1, 'dark'], $select);
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
            "SELECT * FROM {table} WHERE {id} = ? OR json_value({settings}, N'$.\"theme\"') = ?",
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
            "SELECT * FROM {table} WHERE json_value({settings}, N'$.\"phone\".\"work\"') = ?",
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

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_value({settings}, N'$.\"phones\"[1]') = ?",
            $select,
        );
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
            "SELECT * FROM {table} WHERE json_value({settings}, N'$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND ? IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))",
            $select,
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR ? IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))",
            $select,
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\".\"work\"'))",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1]'))",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]'))",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))",
            $select,
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))",
            $select,
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\".\"work\"'))",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1]'))",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]'))",
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
            ->where('id', 1)
            ->whereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND N'languages' IN (SELECT [key] FROM openjson({settings}))",
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
            "SELECT * FROM {table} WHERE {id} = ? OR N'languages' IN (SELECT [key] FROM openjson({settings}))",
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
            "SELECT * FROM {table} WHERE N'work' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))",
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
            "SELECT * FROM {table} WHERE N'1' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))",
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
            "SELECT * FROM {table} WHERE N'3' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"'))",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND NOT N'languages' IN (SELECT [key] FROM openjson({settings}))",
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
            "SELECT * FROM {table} WHERE {id} = ? OR NOT N'languages' IN (SELECT [key] FROM openjson({settings}))",
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
            "SELECT * FROM {table} WHERE NOT N'work' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))",
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
            "SELECT * FROM {table} WHERE NOT N'1' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT N'3' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"'))",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE(SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) >= ?",
            $select,
        );
        $this->assertSameParameters([1], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND (SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) = ?",
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
            "SELECT * FROM {table} WHERE {id}= ? OR (SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) = ?",
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
            "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"personal\".\"languages\"')) = ?",
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
            "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"phones\"[1]')) = ?",
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
            "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]')) = ?",
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
            "SELECT * FROM {table} ORDER BY json_value({logs}, N'$.\"created_at\"') DESC",
            $select,
        );
    }
}
