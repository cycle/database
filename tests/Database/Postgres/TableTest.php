<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests\Postgres;

class TableTest extends \Spiral\Database\Tests\TableTest
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