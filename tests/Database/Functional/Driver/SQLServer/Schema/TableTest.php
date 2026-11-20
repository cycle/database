<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\TableTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class TableTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    public function testAggregationAvgByPassFloat(): void
    {
        $table = $this->database->table('table');
        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->count(), 4);

        //Rounded
        \Testo\Assert::same((int) $table->avg('value'), 13);
    }

    #[\Testo\Test]
    public function testAggregationAvgByPassRealFloat(): void
    {
        $table = $this->database->table('table2');

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->string('name', 64);
        $schema->float('value');
        $schema->save();

        \Testo\Assert::same($table->count(), 0);

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10],
            ],
        );

        \Testo\Assert::same($table->count(), 4);

        //Rounded
        \Testo\Assert::same((float) $table->avg('value'), 13.75);
    }
}
