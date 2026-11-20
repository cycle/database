<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Query;

use Cycle\Database\Tests\Feature\Common\Query\InsertQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Assert;
use Testo\Test;

final class InsertQueryTest extends CommonClass
{
    #[Test]
    public function testCompileQueryDefaults(): void
    {
        $insert = $this->db()->insert('table')->values([]);

        SqlAssert::same((string) $insert, 'INSERT INTO {table} () VALUES ()');
    }

    #[Test]
    public function testSimpleInsertEmptyDataset(): void
    {
        $insert = $this->database->insert()->into('table')->values([]);

        SqlAssert::same($insert, 'INSERT INTO {table} () VALUES ()');
    }

    #[Test]
    public function testInsertMicroseconds(): void
    {
        $schema = $this->schema(
            table: 'with_microseconds',
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]],
        );
        $schema->primary('id');
        $schema->datetime('datetime', 6);
        $schema->save();

        $expected = new \DateTimeImmutable();

        $id = $this->db(
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]],
        )->insert('with_microseconds')->values([
            'datetime' => $expected,
        ])->run();

        $result = $this->db(
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]],
        )->select('datetime')
            ->from('with_microseconds')
            ->where('id', $id)
            ->run()
            ->fetch();

        Assert::same($result['datetime'], $expected->setTimezone($this->database->getDriver()->getTimezone())->format('Y-m-d H:i:s.u'));
    }

    #[Test]
    public function testInsertDatetimeWithoutMicroseconds(): void
    {
        $schema = $this->schema('without_microseconds');
        $schema->primary('id');
        $schema->datetime('datetime');
        $schema->save();

        $expected = new \DateTimeImmutable();

        $id = $this->database->insert('without_microseconds')->values([
            'datetime' => $expected,
        ])->run();

        $result = $this->database->select('datetime')
            ->from('without_microseconds')
            ->where('id', $id)
            ->run()
            ->fetch();

        Assert::same($result['datetime'], $expected->setTimezone($this->database->getDriver()->getTimezone())->format('Y-m-d H:i:s'));
    }
}
