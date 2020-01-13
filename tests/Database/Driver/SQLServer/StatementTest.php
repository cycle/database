<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\SQLServer;

use Spiral\Database\Driver\SQLServer\SQLServerCompiler;

class StatementTest extends \Spiral\Database\Tests\StatementTest
{
    public const DRIVER = 'sqlserver';

    //ROW NUMBER COLUMN! FALLBACK
    public function testCountColumns(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->limit(1)->getIterator();

        $this->assertSame(4, $result->columnCount());
    }

    public function testCountColumnsWithProperOrder(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->limit(1)->orderBy('id')->getIterator();

        $this->assertSame(3, $result->columnCount());
    }

    //ROW NUMBER COLUMN! FALLBACK
    public function testToArray(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(1)->getIterator();

        $this->assertEquals(
            [
                [
                    'id'                          => 1,
                    'name'                        => md5('0'),
                    'value'                       => 0,
                    SQLServerCompiler::ROW_NUMBER => 1
                ]
            ],
            $result->fetchAll()
        );
    }
}
