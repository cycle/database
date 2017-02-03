<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\SQLServer;

class TableTest extends \Spiral\Tests\Database\TableTest
{
    use DriverTrait;

    public function testAggregationAvgByPassFloat()
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
        $this->assertSame(13, $table->avg('value'));
    }

    public function testAggregationAvgByPassRealFloat()
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
        $this->assertSame(13.75, $table->avg('value'));
    }
}