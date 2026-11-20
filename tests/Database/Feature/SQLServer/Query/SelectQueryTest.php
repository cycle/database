<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Query;

use Cycle\Database\Tests\Feature\Common\Query\SelectQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Test;

final class SelectQueryTest extends CommonClass
{
    //FALLBACK!
    #[Test]
    public function testLimitNoOffset(): void
    {
        $select = $this->database->select()->from(['users'])->limit(10);

        SqlAssert::same($select, 'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} BETWEEN ? AND ?');

        SqlAssert::sameParameters($select, [
            1,
            10,
        ]);
    }

    //FALLBACK!
    #[Test]
    public function testLimitAndOffset(): void
    {
        $select = $this->database->select()->from(['users'])->limit(10)->offset(20);

        SqlAssert::same($select, 'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} BETWEEN ? AND ?');

        SqlAssert::sameParameters($select, [
            21,
            30,
        ]);
    }

    //NO FALLBACK
    #[Test]
    public function testLimitAndOffsetAndOrderBy(): void
    {
        $select = $this->database->select()->from(['users'])
            ->limit(10)
            ->orderBy('name')
            ->offset(20);

        SqlAssert::same($select, 'SELECT * FROM {users} ORDER BY {name} ASC OFFSET ? ROWS FETCH FIRST ? ROWS ONLY');


        SqlAssert::sameParameters($select, [
            20,
            10,
        ]);
    }

    //FALLBACK!
    #[Test]
    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        SqlAssert::same($select, 'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} >= ?');

        SqlAssert::sameParameters($select, [
            21,
        ]);
    }

    #[Test]
    public function testSelectForUpdate(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Antony')
            ->forUpdate();

        SqlAssert::same($select, 'SELECT * FROM {users} WITH(UPDLOCK,ROWLOCK) WHERE {name} = ?');
    }

    #[Test]
    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND json_value({settings}, N'$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testSelectWithOrWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR json_value({settings}, N'$.\"theme\"') = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_value({settings}, N'$.\"phone\".\"work\"') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_value({settings}, N'$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_value({settings}, N'$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND ? IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))");
        SqlAssert::sameParameters($select, [1, 'en']);
    }

    #[Test]
    public function testSelectWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR ? IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))");
        SqlAssert::sameParameters($select, [1, 'en']);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\".\"work\"'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1]'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))");
        SqlAssert::sameParameters($select, [1, 'en']);
    }

    #[Test]
    public function testSelectWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"languages\"'))");
        SqlAssert::sameParameters($select, [1, 'en']);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\".\"work\"'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1]'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]'))");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND N'languages' IN (SELECT [key] FROM openjson({settings}))");
    }

    #[Test]
    public function testSelectWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR N'languages' IN (SELECT [key] FROM openjson({settings}))");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE N'work' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE N'1' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE N'3' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"'))");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND NOT N'languages' IN (SELECT [key] FROM openjson({settings}))");
    }

    #[Test]
    public function testSelectWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR NOT N'languages' IN (SELECT [key] FROM openjson({settings}))");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT N'work' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT N'1' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"'))");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT N'3' IN (SELECT [key] FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"'))");
    }

    #[Test]
    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE(SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) >= ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithWhereJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND (SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) = ?");
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

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id}= ? OR (SELECT count(*) FROM openjson({settings}, N'$.\"languages\"')) = ?");
        SqlAssert::sameParameters($select, [1, 4]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"personal\".\"languages\"')) = ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"phones\"[1]')) = ?");
        SqlAssert::sameParameters($select, [2]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, N'$.\"phones\"[1].\"numbers\"[3]')) = ?");
        SqlAssert::sameParameters($select, [5]);
    }

    #[Test]
    public function testOrderByJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->orderBy('logs->created_at', 'DESC');

        SqlAssert::same($select, "SELECT * FROM {table} ORDER BY json_value({logs}, N'$.\"created_at\"') DESC");
    }
}
