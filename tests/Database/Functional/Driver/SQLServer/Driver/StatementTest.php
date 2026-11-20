<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Driver;

// phpcs:ignore
use Cycle\Database\Driver\SQLServer\SQLServerCompiler;
use Cycle\Database\Tests\Functional\Driver\Common\Driver\StatementTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class StatementTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    //ROW NUMBER COLUMN! FALLBACK
    #[\Testo\Test]
    public function testCountColumns(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->limit(1)->getIterator();

        \Testo\Assert::same($result->columnCount(), 4);
    }

    #[\Testo\Test]
    public function testCountColumnsWithProperOrder(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->limit(1)->orderBy('id')->getIterator();

        \Testo\Assert::same($result->columnCount(), 3);
    }

    //ROW NUMBER COLUMN! FALLBACK
    #[\Testo\Test]
    public function testToArray(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(1)->getIterator();

        \Testo\Assert::equals($result->fetchAll(), [
            [
                'id' => 1,
                'name' => \md5('0'),
                'value' => 0,
                SQLServerCompiler::ROW_NUMBER => 1,
            ],
        ]);
    }
}
