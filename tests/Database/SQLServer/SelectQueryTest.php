<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\SQLServer;

class SelectQueryTest extends \Spiral\Tests\Database\SelectQueryTest
{
    use DriverTrait;

    //FALLBACK!
    public function testLimitNoOffset()
    {
        $select = $this->database->select()->from(['users'])->limit(10);

        $this->assertSameQuery(
            "SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} BETWEEN 1 AND 10",
            $select
        );
    }

    //FALLBACK!
    public function testLimitAndOffset()
    {
        $select = $this->database->select()->from(['users'])->limit(10)->offset(20);

        $this->assertSame(10, $select->getLimit());
        $this->assertSame(20, $select->getOffset());

        $this->assertSameQuery(
            "SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} BETWEEN 21 AND 30",
            $select
        );
    }

    //NO FALLBACK
    public function testLimitAndOffsetAndOrderBy()
    {
        $select = $this->database->select()->from(['users'])->limit(10)->orderBy('name')->offset(20);

        $this->assertSame(10, $select->getLimit());
        $this->assertSame(20, $select->getOffset());

        $this->assertSameQuery(
            "SELECT * FROM {users} ORDER BY {name} ASC OFFSET 20 ROWS FETCH FIRST 10 ROWS ONLY",
            $select
        );
    }

    //FALLBACK!
    public function testOffsetNoLimit()
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            "SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDERBY(SELECT NULL)) AS {_ROW_NUMBER_} FROM {users}
            ) AS {ORD_FALLBACK} WHERE {_ROW_NUMBER_} >= 21",
            $select
        );
    }
}