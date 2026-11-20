<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Driver;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\StatementInterface;
use Cycle\Database\Table;
use Cycle\Database\Tests\Fixtures\FooBarEnum;
use Cycle\Database\Tests\Fixtures\IntegerEnum;
use Cycle\Database\Tests\Fixtures\UntypedEnum;
use Cycle\Testing\Database\Assert\SqlAssert;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Spiral\Pagination\Paginator;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[ExceptDriver(Driver::SQLServer)]
class StatementTest extends DatabaseTestCase
{
    public function fillData(?Table $table = null): void
    {
        $table = $table ?? $this->database->table('sample_table');

        for ($i = 0; $i < 10; $i++) {
            $table->insertOne(
                [
                    'name' => \md5((string) $i),
                    'value' => $i * 10,
                ],
            );
        }
    }

    #[Test]
    public function testInstance(): void
    {
        $table = $this->database->table('sample_table');

        Assert::instanceOf($table->select()->getIterator(), StatementInterface::class);

        Assert::instanceOf($table->select()->run()->getPDOStatement(), \PDOStatement::class);
    }

    //We are testing only extended functionality, there is no need to test PDOStatement

    #[Test]
    public function testCountColumns(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->getIterator();

        Assert::same($result->columnCount(), 3);
    }

    #[Test]
    public function testIterateOver(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->getIterator();

        $i = 0;
        foreach ($result as $item) {
            Assert::equals($item['name'], \md5((string) $i));
            Assert::equals($item['value'], $i * 10);

            $i++;
        }

        SqlAssert::same($result->getQueryString(), 'SELECT * FROM {sample_table}');

        Assert::same($i, 10);
    }

    #[Test]
    public function testIterateOverLimit(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(5)->getIterator();

        $i = 0;
        foreach ($result as $item) {
            Assert::equals($item['name'], \md5((string) $i));
            Assert::equals($item['value'], $i * 10);

            $i++;
        }

        Assert::same($i, 5);
    }

    #[Test]
    public function testIterateOverOffset(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->offset(5)->getIterator();

        $i = 5;
        foreach ($result as $item) {
            Assert::equals($item['name'], \md5((string) $i));
            Assert::equals($item['value'], $i * 10);

            $i++;
        }

        Assert::same($i, 10);
    }

    #[Test]
    public function testIterateOverOffsetAndLimit(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->offset(5)->limit(2)->getIterator();

        $i = 5;
        foreach ($result as $item) {
            Assert::equals($item['name'], \md5((string) $i));
            Assert::equals($item['value'], $i * 10);

            $i++;
        }

        Assert::same($i, 7);
    }

    #[Test]
    public function testPaginate(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $paginator = new Paginator(2);

        $select = $table->select();
        $paginator->withPage(1)->paginate($select);

        $i = 0;
        foreach ($select as $item) {
            Assert::equals($item['name'], \md5((string) $i));
            Assert::equals($item['value'], $i * 10);

            $i++;
        }

        Assert::same($i, 2);

        $select = $table->select();
        $paginator->withPage(2)->paginate($select);
        $i = 2;
        foreach ($select as $item) {
            Assert::equals($item['name'], \md5((string) $i));
            Assert::equals($item['value'], $i * 10);

            $i++;
        }

        Assert::same($i, 4);

        $select = $table->select();
        $paginator->withPage(3)->paginate($select);

        $i = 4;
        foreach ($select as $item) {
            Assert::equals($item['name'], \md5((string) $i));
            Assert::equals($item['value'], $i * 10);

            $i++;
        }

        Assert::same($i, 6);

        $paginator = $paginator->withLimit(6);

        $select = $table->select();
        $paginator->withPage(4)->paginate($select); //Forced last page

        $i = 6;
        foreach ($select as $item) {
            Assert::equals($item['name'], \md5((string) $i));
            Assert::equals($item['value'], $i * 10);

            $i++;
        }

        Assert::same($i, 10);
    }

    #[Test]
    public function testDebugString(): void
    {
        $table = $this->database->table('sample_table');
        $result = $table->select()->getIterator();

        SqlAssert::same($result->getQueryString(), 'SELECT * FROM {sample_table}');
    }

    #[Test]
    public function testToArray(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->select()->limit(1)->getIterator();

        Assert::equals($result->fetchAll(), [
            ['id' => 1, 'name' => \md5('0'), 'value' => 0],
        ]);
    }

    #[Test]
    public function testToObj(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $result = $table->limit(1)->fetchAll(StatementInterface::FETCH_OBJ);

        Assert::object($result[0]);
    }

    #[Test]
    public function testClose(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();
        $result = $table->select()->getIterator();

        Assert::null($result->close());
    }

    #[Test]
    public function testSpanishInquisition(): void
    {
        $driver = $this->database->getDriver();
        $driver->connect();
        Assert::true($driver->isConnected());

        //And now something different
        $driver->disconnect();
        Assert::false($driver->isConnected());
    }

    #[Test]
    public function testChunks(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select();

        $count = 0;
        $select->runChunks(
            1,
            static function ($result) use (&$count): void {
                Assert::instanceOf($result, StatementInterface::class);
                Assert::equals($result->fetchColumn(), $count + 1);

                $count++;
            },
        );

        Assert::same($count, 10);
    }

    #[Test]
    public function testChunksExif(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select();

        $count = 0;
        $select->runChunks(
            1,
            static function ($result) use (&$count) {
                Assert::instanceOf($result, StatementInterface::class);

                $count++;
                if ($count == 5) {
                    return false;
                }
            },
        );

        Assert::same($count, 5);
    }

    #[Test]
    public function testChunksProcessLastPartialChunk(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select()->orderBy('id');

        // 10 rows split by chunks of 3: the last chunk holds the single remaining row (10 % 3 == 1)
        $visited = [];
        $select->runChunks(
            3,
            static function (StatementInterface $result) use (&$visited): void {
                foreach ($result as $row) {
                    $visited[] = $row['id'];
                }
            },
        );

        Assert::equals($visited, \range(1, 10));
    }

    #[Test]
    public function testChunksWithLimitGreaterThanCount(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select()->orderBy('id');

        // chunk size larger than the total row count must still yield every row in a single chunk
        $visited = [];
        $chunks = 0;
        $select->runChunks(
            100,
            static function (StatementInterface $result) use (&$visited, &$chunks): void {
                $chunks++;
                foreach ($result as $row) {
                    $visited[] = $row['id'];
                }
            },
        );

        Assert::same($chunks, 1);
        Assert::equals($visited, \range(1, 10));
    }

    #[Test]
    public function testChunksOnEmptyResultNeverInvokesCallback(): void
    {
        $table = $this->database->table('sample_table');
        // no data inserted

        $select = $table->select();

        $invoked = false;
        $select->runChunks(
            5,
            static function () use (&$invoked): void {
                $invoked = true;
            },
        );

        Assert::false($invoked);
    }

    #[Test]
    public function testChunksPassOffsetAndCountToCallback(): void
    {
        $table = $this->database->table('sample_table');
        $this->fillData();

        $select = $table->select();

        $offsets = [];
        $counts = [];
        $select->runChunks(
            3,
            static function (StatementInterface $result, int $offset, int $count) use (&$offsets, &$counts): void {
                $offsets[] = $offset;
                $counts[] = $count;
            },
        );

        Assert::same($offsets, [0, 3, 6, 9]);
        Assert::same($counts, [10, 10, 10, 10]);
    }

    #[Test]
    public function testNativeParameters(): void
    {
        $this->fillData();

        $row = $this->database->query(
            'SELECT * FROM sample_table WHERE id = ?',
            [6],
        )->fetch();

        $i = 5;
        Assert::equals($row['name'], \md5((string) $i));
        Assert::equals($row['value'], $i * 10);

        $row = $this->database->query(
            'SELECT * FROM sample_table WHERE id = :id',
            [':id' => 5],
        )->fetch();

        $i = 4;
        Assert::equals($row['name'], \md5((string) $i));
        Assert::equals($row['value'], $i * 10);
    }

    #[Test]
    public function testDatetimeInQuery(): void
    {
        $this->fillData();

        Assert::same($this->database->sample_table->select()
            ->where('name', '!=', new \DateTime('1990-01-01'))
            ->count(), 10);
    }

    #[Test]
    public function testIntegerEnumInQuery(): void
    {
        $this->fillData();

        Assert::same($this->database->sample_table->select()
            ->where('value', '=', IntegerEnum::TEN)
            ->count(), 1);
    }

    #[Test]
    public function testStringEnumInQuery(): void
    {
        $table = $this->database->table('sample_table');

        $table->insertOne(
            [
                'name' => 'foo',
                'value' => 100500,
            ],
        );

        Assert::same($this->database->sample_table->select()
            ->where('name', '=', FooBarEnum::FOO)
            ->count(), 1);
    }

    #[Test]
    public function testUntypedEnumInQuery(): void
    {
        $this->fillData();

        Expect::exception(StatementException::class);

        $this->database->sample_table->select()
            ->where('value', '=', UntypedEnum::FOO)
            ->count();
    }

    #[Test]
    public function testNativeParametersError(): void
    {
        $this->fillData();

        Expect::exception(StatementException::class);

        // Binding an array raises a warning; the driver maps it once it is an exception.
        \set_error_handler(static fn(int $code, string $message): never => throw new \ErrorException($message, 0, $code));
        try {
            $this->database->query(
                'SELECT * FROM sample_table WHERE id = :id',
                [':id' => [1, 2]],
            )->fetch();
        } finally {
            \restore_error_handler();
        }
    }

    #[Test]
    public function testUnpackArrayFromParameter(): void
    {
        $this->fillData();

        $rows = $this->database->query(
            'SELECT * FROM sample_table WHERE id IN (?, ?, ?) ORDER BY id ASC',
            [1, 2, 3],
        )->fetchAll();

        $i = 0;
        Assert::equals($rows[0]['name'], \md5((string) $i));
        Assert::equals($rows[0]['value'], $i * 10);

        $i = 1;
        Assert::equals($rows[1]['name'], \md5((string) $i));
        Assert::equals($rows[1]['value'], $i * 10);

        $i = 2;
        Assert::equals($rows[2]['name'], \md5((string) $i));
        Assert::equals($rows[2]['value'], $i * 10);
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
}
