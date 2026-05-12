<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Driver;

use Cycle\Database\Driver\SQLServer\SQLServerCursorOptions;
use Cycle\Database\Driver\SQLServer\CursorType;
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
    public function testCursorRespectsTypeOption(CursorType $type): void
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

    public function testCursorUsesExplicitName(): void
    {
        $this->fillRows(3);

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                    new SQLServerCursorOptions(name: 'my_export_cursor'),
                ),
                false,
            ),
        );

        $this->assertCount(3, $rows);
    }

    public function testCursorRejectsInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/valid t-sql identifier/i');

        new SQLServerCursorOptions(name: 'bad name');
    }

    public function cursorTypes(): \Generator
    {
        yield 'STATIC' => [CursorType::Static];
        yield 'KEYSET' => [CursorType::Keyset];
        yield 'DYNAMIC' => [CursorType::Dynamic];
        yield 'FAST_FORWARD' => [CursorType::FastForward];
    }
}
