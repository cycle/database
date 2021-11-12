<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\TableTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class TableTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testAggregationAvgByPassFloat(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->count());

        //Rounded
        $this->assertSame(13, (int)$table->avg('value'));
    }

    public function testAggregationAvgByPassRealFloat(): void
    {
        $table = $this->database->table('table2');

        $schema = $table->getSchema();
        $schema->primary('id');
        $schema->string('name', 64);
        $schema->float('value');
        $schema->save();

        $this->assertSame(0, $table->count());

        $table->insertMultiple(
            ['name', 'value'],
            [
                ['Anton', 10],
                ['John', 20],
                ['Bob', 15],
                ['Charlie', 10]
            ]
        );

        $this->assertSame(4, $table->count());

        //Rounded
        $this->assertSame(13.75, (float)$table->avg('value'));
    }
}
