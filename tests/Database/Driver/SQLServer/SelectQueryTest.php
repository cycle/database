<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\SQLServer;

class SelectQueryTest extends \Spiral\Database\Tests\SelectQueryTest
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
                10
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
                30
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
                10
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
                21
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
}
