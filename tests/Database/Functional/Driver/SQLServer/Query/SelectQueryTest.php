<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SelectQueryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    //FALLBACK!
    public function testLimitNoOffset(): void
    {
        $select = $this->database->select()->from(['users'])->limit(10);

        $this->assertSameQuery(
            'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} BETWEEN ? AND ?',
            $select
        );

        $this->assertSameParameters(
            [
                1,
                10,
            ],
            $select
        );
    }

    //FALLBACK!
    public function testLimitAndOffset(): void
    {
        $select = $this->database->select()->from(['users'])->limit(10)->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} BETWEEN ? AND ?',
            $select
        );

        $this->assertSameParameters(
            [
                21,
                30,
            ],
            $select
        );
    }

    //NO FALLBACK
    public function testLimitAndOffsetAndOrderBy(): void
    {
        $select = $this->database->select()->from(['users'])
            ->limit(10)
            ->orderBy('name')
            ->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM {users} ORDER BY {name} ASC OFFSET ? ROWS FETCH FIRST ? ROWS ONLY',
            $select
        );


        $this->assertSameParameters(
            [
                20,
                10,
            ],
            $select
        );
    }

    //FALLBACK!
    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} >= ?',
            $select
        );

        $this->assertSameParameters(
            [
                21,
            ],
            $select
        );
    }

    public function testSelectForUpdate(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Antony')
            ->forUpdate();

        $this->assertSameQuery(
            'SELECT * FROM {users} WITH(UPDLOCK,ROWLOCK) WHERE {name} = ?',
            $select
        );
    }

    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_value({settings}, '$.\"theme\"') = ?",
            $select
        );
        $this->assertSameParameters(['dark'], $select);
    }

    public function testSelectWithAndWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->andWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND json_value({settings}, '$.\"theme\"') = ?",
            $select
        );
        $this->assertSameParameters([1, 'dark'], $select);
    }

    public function testSelectWithOrWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR json_value({settings}, '$.\"theme\"') = ?",
            $select
        );
        $this->assertSameParameters([1, 'dark'], $select);
    }

    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_value({settings}, '$.\"phone\".\"work\"') = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_value({settings}, '$.\"phones\"[1]') = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_value({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters(['en'], $select);
    }

    public function testSelectWithAndWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->andWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND ? IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    public function testSelectWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR ? IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    public function testSelectWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, '$.\"phones\".\"work\"'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, '$.\"phones\"[1]'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? IN (SELECT [value] FROM openjson({settings}, '$.\"phones\"[1].\"numbers\"[3]'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters(['en'], $select);
    }

    public function testSelectWithAndWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->andWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    public function testSelectWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"languages\"'))",
            $select
        );
        $this->assertSameParameters([1, 'en'], $select);
    }

    public function testSelectWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"phones\".\"work\"'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"phones\"[1]'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ? NOT IN (SELECT [value] FROM openjson({settings}, '$.\"phones\"[1].\"numbers\"[3]'))",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"languages\"')) = ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE(SELECT count(*) FROM openjson({settings}, '$.\"languages\"')) >= ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testSelectWithAndJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->andWhereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND (SELECT count(*) FROM openjson({settings}, '$.\"languages\"')) = ?",
            $select
        );
        $this->assertSameParameters([1, 3], $select);
    }

    public function testSelectWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id}= ? OR (SELECT count(*) FROM openjson({settings}, '$.\"languages\"')) = ?",
            $select
        );
        $this->assertSameParameters([1, 4], $select);
    }

    public function testSelectWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->personal->languages', 1);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"personal\".\"languages\"')) = ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testSelectWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]', 2);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"phones\"[1]')) = ?",
            $select
        );
        $this->assertSameParameters([2], $select);
    }

    public function testSelectWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE (SELECT count(*) FROM openjson({settings}, '$.\"phones\"[1].\"numbers\"[3]')) = ?",
            $select
        );
        $this->assertSameParameters([5], $select);
    }
}
