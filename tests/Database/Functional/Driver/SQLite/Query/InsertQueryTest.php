<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\InsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class InsertQueryTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    public function testSimpleInsertMultipleRows(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) SELECT ? AS {name}, ? AS {balance}
UNION ALL SELECT ?, ?',
            $insert
        );
    }

    public function testSimpleInsertMultipleRows2(): void
    {
        $insert = $this->database->insert()->into('table')
            ->columns('name', 'balance')
            ->values('Anton', 100)
            ->values('John', 200)
            ->values('Pitt', 200);

        $this->assertSameQuery(
            'INSERT INTO {table} ({name}, {balance}) SELECT ? AS {name}, ? AS {balance}'
            . ' UNION ALL SELECT ?, ?'
            . ' UNION ALL SELECT ?, ?',
            $insert
        );
    }

    public function testInsertMicroseconds(): void
    {
        $schema = $this->schema(table: 'with_microseconds', driverConfig: ['datetimeWithMicroseconds' => true]);
        $schema->primary('id');
        $schema->datetime('datetime', 6);
        $schema->save();

        $expected = new \DateTimeImmutable();

        $id = $this->db(driverConfig: ['datetimeWithMicroseconds' => true])->insert('with_microseconds')->values([
            'datetime' => $expected,
        ])->run();

        $result = $this->db(driverConfig: ['datetimeWithMicroseconds' => true])->select('datetime')
            ->from('with_microseconds')
            ->where('id', $id)
            ->run()
            ->fetch();

        $this->assertSame(
            $expected->setTimezone($this->database->getDriver()->getTimezone())->format('Y-m-d H:i:s.u'),
            $result['datetime']
        );
    }

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

        $this->assertSame(
            $expected->setTimezone($this->database->getDriver()->getTimezone())->format('Y-m-d H:i:s'),
            $result['datetime']
        );
    }
}
