<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Query;

use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Feature\Common\Query\SelectWithJoinQueryTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class SelectWithJoinQueryTest extends CommonClass
{
    #[Test]
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
                on: new Fragment('true'),
            );

        $select->sqlStatement();

        // Verify that the join name has a correct format in the cache
        Assert::array($ref->getValue($compiler))->hasKeys('s__temperature as t*,jhLEFTLATERALp_s__humidity*,wANDh.date<=?_1_1onANDtruewANDt.date=?_1_1');
    }
}
