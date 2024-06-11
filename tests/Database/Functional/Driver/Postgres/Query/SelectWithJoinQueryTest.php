<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectWithJoinQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class SelectWithJoinQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testCacheLeftJoinLateral(): void
    {
        $compiler = $this->database->select()->getDriver()->getQueryCompiler();

        $ref = new \ReflectionProperty($compiler, 'cache');
        $ref->setAccessible(true);
        $ref->setValue($compiler, []);

        $select = $this->database->select()
            ->from('temperature as t')
            ->where('t.date', '2022-01-05')
            ->join(
                type: 'LEFT JOIN LATERAL',
                outer: $this->database->select()
                    ->from('humidity')
                    ->where('h.date', '<=', 't.date'),
                alias: 'h',
                on: new Fragment('true')
            );

        $select->sqlStatement();

        // Verify that the join name has a correct format in the cache
        $this->assertArrayHasKey(
            's__temperature as t*,jhLEFTLATERALp_s__humidity*,wANDh.date<=?_1_1onANDtruewANDt.date=?_1_1',
            $ref->getValue($compiler)
        );
    }
}
