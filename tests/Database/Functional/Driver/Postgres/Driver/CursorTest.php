<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Driver;

use Cycle\Database\Driver\Postgres\PostgresCursorOptions;
use Cycle\Database\Exception\DriverException;
// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\CursorTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class CursorTest extends CommonClass
{
    public const DRIVER = 'postgres';

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

        $this->assertCount(25, $rows);

        // 25 rows / chunkSize 10 → three FETCH FORWARD calls: 10 + 10 + 5.
        for ($i = 0; $i < 25; $i++) {
            $this->assertSame(\md5((string) $i), $rows[$i]['name']);
            $this->assertSame($i * 10, (int) $rows[$i]['value']);
        }
    }

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

        $this->assertCount(10, $rows);
    }

    public function testCursorChunkSizeMustBePositive(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches('/chunk size/i');

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

        $this->assertCount(3, $rows);
    }

    public function testCursorRejectsInvalidName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/valid postgresql identifier/i');

        new PostgresCursorOptions(name: 'bad"name; DROP TABLE');
    }

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
        $this->assertSame(\md5('0'), $first['name']);

        // Commit the transaction. WITH HOLD materializes the rest on the server.
        $this->database->commit();

        // Continue iterating after COMMIT — must still work.
        $remaining = [];
        $gen->next();
        while ($gen->valid()) {
            $remaining[] = $gen->current();
            $gen->next();
        }

        $this->assertCount(4, $remaining);
        $this->assertSame(\md5('4'), $remaining[3]['name']);
    }
}
