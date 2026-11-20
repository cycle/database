<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Driver;

use Cycle\Database\Driver\Postgres\PostgresCursorOptions;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Tests\Feature\Common\Driver\CursorTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class CursorTest extends CommonClass
{
    #[Test]
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

        Assert::count($rows, 25);

        // 25 rows / chunkSize 10 → three FETCH FORWARD calls: 10 + 10 + 5.
        for ($i = 0; $i < 25; $i++) {
            Assert::same($rows[$i]['name'], \md5((string) $i));
            Assert::same((int) $rows[$i]['value'], $i * 10);
        }
    }

    #[Test]
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

        Assert::count($rows, 10);
    }

    #[Test]
    public function testCursorChunkSizeMustBePositive(): void
    {
        Expect::exception(DriverException::class)->withMessageMatchingRegex('/chunk size/i');

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

    #[Test]
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

        Assert::count($rows, 3);
    }

    #[Test]
    public function testCursorRejectsInvalidName(): void
    {
        Expect::exception(\InvalidArgumentException::class)->withMessageMatchingRegex('/valid postgresql identifier/i');

        new PostgresCursorOptions(name: 'bad"name; DROP TABLE');
    }

    #[Test]
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
        Assert::same($first['name'], \md5('0'));

        // Commit the transaction. WITH HOLD materializes the rest on the server.
        $this->database->commit();

        // Continue iterating after COMMIT — must still work.
        $remaining = [];
        $gen->next();
        while ($gen->valid()) {
            $remaining[] = $gen->current();
            $gen->next();
        }

        Assert::count($remaining, 4);
        Assert::same($remaining[3]['name'], \md5('4'));
    }

    #[Test]
    public function testCursorAbandonedAfterRollbackClosesQuietly(): void
    {
        $this->fillRows(3);

        $this->database->begin();
        $gen = $this->database->cursor(
            $this->database->select()->from('sample_table')->orderBy('id'),
            new PostgresCursorOptions(chunkSize: 1),
        );
        $gen->rewind();
        $this->database->rollback();

        unset($gen);

        Assert::same($this->database->select()->from('sample_table')->count(), 3);
    }
}
