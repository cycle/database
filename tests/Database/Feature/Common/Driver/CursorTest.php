<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Driver;

use Cycle\Database\Exception\DriverException;
use Cycle\Database\StatementInterface;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[OnlyDriver(Driver::SQLite)]
class CursorTest extends DatabaseTestCase
{
    #[Test]
    public function testCursorRequiresActiveTransaction(): void
    {
        $this->fillRows(3);

        Expect::exception(DriverException::class)->withMessageMatchingRegex('/active transaction/i');

        $gen = $this->database->cursor($this->database->select()->from('sample_table'));
        // Generator is lazy — must iterate to trigger the check.
        \iterator_to_array($gen);
    }

    #[Test]
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

        Assert::count($rows, 5);
        Assert::same($rows[0]['name'], \md5('0'));
        Assert::same((int) $rows[4]['value'], 40);
    }

    #[Test]
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

        Assert::count($rows, 25);

        for ($i = 0; $i < 25; $i++) {
            Assert::same($rows[$i]['name'], \md5((string) $i));
            Assert::same((int) $rows[$i]['value'], $i * 10);
        }
    }

    #[Test]
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

        Assert::same($rows, []);
    }

    #[Test]
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

        Assert::count($rows, 2);
        Assert::array($rows[0])->hasKeys(0);
        Assert::array($rows[0])->doesNotHaveKeys('id');
        Assert::same($rows[0][1], \md5('0'));
    }

    #[Test]
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

        Assert::count($rows, 5);
        Assert::same((int) $rows[0]['value'], 50);
        Assert::same((int) $rows[4]['value'], 90);
    }

    #[Test]
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

        Assert::same($seen['count'], 3);
        Assert::same($seen['total'], 50);
    }

    #[Test]
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
            Assert::count($rows, 7);

            // Same transaction, fresh query — must work
            $next = $this->database->select('COUNT(*)')->from('sample_table')->run()->fetch(StatementInterface::FETCH_NUM);
            Assert::same((int) $next[0], 7);
        });
    }

    #[BeforeTest]
    public function setUp(): void
    {
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
