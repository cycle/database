<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
declare(strict_types=1);

namespace Spiral\Database\Tests\Postgres;

class TableTest extends \Spiral\Database\Tests\TableTest
{
    public const DRIVER = 'postgres';

    //Applause, PG
    public function testGetColumns(): void
    {
        $table = $this->database->table('table');
        $this->assertSame(0, $table->count());

        $expected = [
            'id'    => 'primary',
            'name'  => 'text',
            'value' => 'integer'
        ];
        arsort($expected);


        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = $column->getAbstractType();
        }

        arsort($columns);

        $this->assertSame($expected, $columns);
    }
}
