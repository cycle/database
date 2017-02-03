<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\SQLServer;

use Spiral\Database\Drivers\SQLServer\SQLServerCompiler;

class QueryResultTest extends \Spiral\Tests\Database\QueryResultTest
{
    use DriverTrait;

    //ROW NUMBER COLUMN! FALLBACK
    public function testCountColumns()
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->limit(1)->getIterator();

        $this->assertSame(4, $result->countColumns());
    }

    public function testCountColumnsWithProperOrder()
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->limit(1)->orderBy('id')->getIterator();

        $this->assertSame(3, $result->countColumns());
    }

    //ROW NUMBER COLUMN! FALLBACK
    public function testToArray()
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(1)->getIterator();

        $this->assertEquals([
            ['id' => 1, 'name' => md5(0), 'value' => 0, SQLServerCompiler::ROW_NUMBER => 1]
        ], $result->toArray());
    }
}