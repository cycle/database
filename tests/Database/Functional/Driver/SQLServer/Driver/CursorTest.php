<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Driver;

use Cycle\Database\Driver\SQLServer\CursorType;
use Cycle\Database\Driver\SQLServer\SQLServerCursorOptions;
// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\CursorTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class CursorTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('cursorTypes')]
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

        \Testo\Assert::count($rows, 5);
        \Testo\Assert::same($rows[0]['name'], \md5('0'));
        \Testo\Assert::same((int) $rows[4]['value'], 40);
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($rows, 3);
    }

    #[\Testo\Test]
    public function testCursorRejectsInvalidName(): void
    {
        \Testo\Expect::exception(\InvalidArgumentException::class)->withMessageMatchingRegex('/valid t-sql identifier/i');

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
