<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Driver;

use Cycle\Database\Driver\SQLServer\CursorType;
use Cycle\Database\Driver\SQLServer\SQLServerCursorOptions;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Feature\Common\Driver\CursorTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::SQLServer)]
final class CursorTest extends CommonClass
{
    #[Test]
    #[DataProvider('cursorTypes')]
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

        Assert::count($rows, 5);
        Assert::same($rows[0]['name'], \md5('0'));
        Assert::same((int) $rows[4]['value'], 40);
    }

    #[Test]
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

        Assert::count($rows, 3);
    }

    #[Test]
    public function testFailedDeclareSurfacesItsOwnError(): void
    {
        $this->fillRows(2);

        $rows = $this->database->transaction(function (): array {
            $error = null;
            try {
                \iterator_to_array($this->database->cursor(
                    $this->database->select()->from('missing_table'),
                    new SQLServerCursorOptions(name: 'reused_cursor'),
                ));
            } catch (\Throwable $error) {
            }

            Assert::instanceOf($error, StatementException::class);
            Assert::string($error->getMessage())->contains('missing_table');

            return \iterator_to_array($this->database->cursor(
                $this->database->select()->from('sample_table'),
                new SQLServerCursorOptions(name: 'reused_cursor'),
            ), false);
        });

        Assert::count($rows, 2);
    }

    #[Test]
    public function testCursorRejectsInvalidName(): void
    {
        Expect::exception(\InvalidArgumentException::class)->withMessageMatchingRegex('/valid t-sql identifier/i');

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
