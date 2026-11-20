<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Driver;

use Cycle\Database\Driver\Postgres\PostgresCursorOptions;
use Cycle\Database\Exception\DriverException;
// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\CursorTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class CursorTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testCursorYieldsAcrossMultipleFetchChunks(): void
    {
        $this->fillRows(25);

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                    new PostgresCursorOptions(chunkSize: 10),
                ),
                false,
            ),
        );

        \Testo\Assert::count($rows, 25);

        // 25 rows / chunkSize 10 → three FETCH FORWARD calls: 10 + 10 + 5.
        for ($i = 0; $i < 25; $i++) {
            \Testo\Assert::same($rows[$i]['name'], \md5((string) $i));
            \Testo\Assert::same((int) $rows[$i]['value'], $i * 10);
        }
    }

    #[\Testo\Test]
    public function testCursorYieldsExactlyOneChunk(): void
    {
        $this->fillRows(10);

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                    new PostgresCursorOptions(chunkSize: 10),
                ),
                false,
            ),
        );

        \Testo\Assert::count($rows, 10);
    }

    #[\Testo\Test]
    public function testCursorChunkSizeMustBePositive(): void
    {
        \Testo\Expect::exception(DriverException::class)->withMessageMatchingRegex('/chunk size/i');

        $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table'),
                    new PostgresCursorOptions(chunkSize: 0),
                ),
                false,
            ),
        );
    }

    #[\Testo\Test]
    public function testCursorUsesExplicitName(): void
    {
        $this->fillRows(3);
        $name = 'my_export_cursor';

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                    new PostgresCursorOptions(name: $name),
                ),
                false,
            ),
        );

        \Testo\Assert::count($rows, 3);
    }

    #[\Testo\Test]
    public function testCursorRejectsInvalidName(): void
    {
        \Testo\Expect::exception(\InvalidArgumentException::class)->withMessageMatchingRegex('/valid postgresql identifier/i');

        new PostgresCursorOptions(name: 'bad"name; DROP TABLE');
    }

    #[\Testo\Test]
    public function testCursorWithHoldSurvivesCommit(): void
    {
        $this->fillRows(5);

        // Open cursor inside a transaction with WITH HOLD, commit, then keep fetching.
        $this->database->begin();
        $gen = $this->database->cursor(
            $this->database->select()->from('sample_table')->orderBy('id'),
            new PostgresCursorOptions(withHold: true),
        );

        // Pull first row inside the transaction.
        $gen->rewind();
        $first = $gen->current();
        \Testo\Assert::same($first['name'], \md5('0'));

        // Commit the transaction. WITH HOLD materializes the rest on the server.
        $this->database->commit();

        // Continue iterating after COMMIT — must still work.
        $remaining = [];
        $gen->next();
        while ($gen->valid()) {
            $remaining[] = $gen->current();
            $gen->next();
        }

        \Testo\Assert::count($remaining, 4);
        \Testo\Assert::same($remaining[3]['name'], \md5('4'));
    }
}
