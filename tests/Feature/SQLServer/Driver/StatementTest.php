<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Driver;

use Cycle\Database\Driver\SQLServer\SQLServerCompiler;
use Cycle\Database\Tests\Feature\Common\Driver\StatementTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::SQLServer)]
final class StatementTest extends CommonClass
{
    //ROW NUMBER COLUMN! FALLBACK
    #[Test]
    public function testCountColumns(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->limit(1)->getIterator();

        Assert::same($result->columnCount(), 4);
    }

    #[Test]
    public function testCountColumnsWithProperOrder(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->limit(1)->orderBy('id')->getIterator();

        Assert::same($result->columnCount(), 3);
    }

    //ROW NUMBER COLUMN! FALLBACK
    #[Test]
    public function testToArray(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(1)->getIterator();

        Assert::equals($result->fetchAll(), [
            [
                'id' => 1,
                'name' => \md5('0'),
                'value' => 0,
                SQLServerCompiler::ROW_NUMBER => 1,
            ],
        ]);
    }
}
