<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Driver;

use Cycle\Database\Driver\SQLServer\SQLServerCursorOptions;
use Cycle\Database\Driver\SQLServer\SQLServerCursorType;
// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\CursorTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CursorTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    /**
     * @dataProvider cursorTypes
     */
    public function testCursorRespectsTypeOption(SQLServerCursorType $type): void
    {
        $this->fillRows(5);

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                    new SQLServerCursorOptions(type: $type),
                ),
                false,
            ),
        );

        $this->assertCount(5, $rows);
        $this->assertSame(\md5('0'), $rows[0]['name']);
        $this->assertSame(40, (int) $rows[4]['value']);
    }

    public function cursorTypes(): \Generator
    {
        yield 'STATIC' => [SQLServerCursorType::Static];
        yield 'KEYSET' => [SQLServerCursorType::Keyset];
        yield 'DYNAMIC' => [SQLServerCursorType::Dynamic];
        yield 'FAST_FORWARD' => [SQLServerCursorType::FastForward];
    }
}
