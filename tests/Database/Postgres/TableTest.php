<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\Postgres;

class TableTest extends \Spiral\Tests\Database\TableTest
{
    use DriverTrait;

    //Applause, PG
    public function testGetColumns()
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $expected = [
            'id'    => 'primary',
            'name'  => 'text',
            'value' => 'integer'
        ];
        arsort($expected);

        $columns = $table->getColumns();
        arsort($columns);

        $this->assertSame($expected, $columns);
    }
}