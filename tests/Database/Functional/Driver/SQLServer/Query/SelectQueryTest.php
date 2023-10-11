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

    public function testJsonWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('settings->theme', 'dark');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select
        );
        $this->assertSameParameters(['dark'], $select);
    }

    public function testNestedJsonWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('settings->phone->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phone\".\"work\"')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testArrayJsonWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1]')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testNestedArrayJsonWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }
}
