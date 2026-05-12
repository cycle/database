<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Driver;

use Cycle\Database\Exception\DriverException;
use Cycle\Database\StatementInterface;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Utils\DontGenerateAttribute;

#[DontGenerateAttribute]
abstract class CursorTest extends BaseTest
{
    public function testCursorRequiresActiveTransaction(): void
    {
        $this->fillRows(3);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches('/active transaction/i');

        $gen = $this->database->cursor($this->database->select()->from('sample_table'));
        // Generator is lazy — must iterate to trigger the check.
        \iterator_to_array($gen);
    }

    public function testCursorYieldsAllRowsForSmallDataset(): void
    {
        $this->fillRows(5);

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                ),
                false,
            ),
        );

        $this->assertCount(5, $rows);
        $this->assertSame(\md5('0'), $rows[0]['name']);
        $this->assertSame(40, (int) $rows[4]['value']);
    }

    public function testCursorYieldsAllRowsForLargerDataset(): void
    {
        $this->fillRows(25);

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                ),
                false,
            ),
        );

        $this->assertCount(25, $rows);

        for ($i = 0; $i < 25; $i++) {
            $this->assertSame(\md5((string) $i), $rows[$i]['name']);
            $this->assertSame($i * 10, (int) $rows[$i]['value']);
        }
    }

    public function testCursorEmptyResult(): void
    {
        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table'),
                ),
                false,
            ),
        );

        $this->assertSame([], $rows);
    }

    public function testCursorRespectsFetchNumMode(): void
    {
        $this->fillRows(2);

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select('id', 'name', 'value')->from('sample_table')->orderBy('id'),
                    mode: StatementInterface::FETCH_NUM,
                ),
                false,
            ),
        );

        $this->assertCount(2, $rows);
        $this->assertArrayHasKey(0, $rows[0]);
        $this->assertArrayNotHasKey('id', $rows[0]);
        $this->assertSame(\md5('0'), $rows[0][1]);
    }

    public function testCursorWithBoundParameters(): void
    {
        $this->fillRows(10);

        $rows = $this->database->transaction(
            fn() => \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()
                        ->from('sample_table')
                        ->where('value', '>=', 50)
                        ->orderBy('id'),
                ),
                false,
            ),
        );

        $this->assertCount(5, $rows);
        $this->assertSame(50, (int) $rows[0]['value']);
        $this->assertSame(90, (int) $rows[4]['value']);
    }

    public function testCursorReleasesOnEarlyBreak(): void
    {
        $this->fillRows(50);

        $seen = $this->database->transaction(function () {
            $count = 0;
            foreach (
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                ) as $row
            ) {
                $count++;
                if ($count === 3) {
                    break;
                }
            }

            // After break, the cursor/statement must be released (finally fires when
            // the generator is GC'd). We should be able to run a fresh query on the
            // same transaction immediately.
            $follow = $this->database->select('COUNT(*)')->from('sample_table')->run()->fetch(StatementInterface::FETCH_NUM);
            return ['count' => $count, 'total' => (int) $follow[0]];
        });

        $this->assertSame(3, $seen['count']);
        $this->assertSame(50, $seen['total']);
    }

    public function testCursorFinishesCleanlyAndAllowsNextQuery(): void
    {
        $this->fillRows(7);

        $this->database->transaction(function (): void {
            $rows = \iterator_to_array(
                $this->database->cursor(
                    $this->database->select()->from('sample_table')->orderBy('id'),
                ),
                false,
            );
            $this->assertCount(7, $rows);

            // Same transaction, fresh query — must work
            $next = $this->database->select('COUNT(*)')->from('sample_table')->run()->fetch(StatementInterface::FETCH_NUM);
            $this->assertSame(7, (int) $next[0]);
        });
    }

    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('sample_table')->getSchema();
        $schema->primary('id');
        $schema->string('name', 64);
        $schema->integer('value');
        $schema->save();
    }

    protected function fillRows(int $count): void
    {
        $table = $this->database->table('sample_table');
        for ($i = 0; $i < $count; $i++) {
            $table->insertOne([
                'name' => \md5((string) $i),
                'value' => $i * 10,
            ]);
        }
    }
}
