<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\TableTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::SQLServer)]
final class TableTest extends CommonClass
{
    #[Test]
    public function testAggregationAvgByPassFloat(): void
    {
        $table = $this->database->table('table');
        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->count(), 4);

        //Rounded
        Assert::same((int) $table->avg('value'), 13);
    }

    #[Test]
    public function testAggregationAvgByPassRealFloat(): void
    {
        $table = $this->database->table('table2');

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->string('name', 64);
        $schema->float('value');
        $schema->save();

        Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        Assert::same($table->count(), 4);

        //Rounded
        Assert::same((float) $table->avg('value'), 13.75);
    }
}
