<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\InsertQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class InsertQueryTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testCompileQueryDefaults(): void
    {
        $insert = $this->db()->insert('table')->values([]);

        $this->assertSameQuery(
            'INSERT INTO {table} () VALUES ()',
            (string) $insert
        );
    }

    public function testSimpleInsertEmptyDataset(): void
    {
        $insert = $this->database->insert()->into('table')->values([]);

        $this->assertSameQuery(
            'INSERT INTO {table} () VALUES ()',
            $insert
        );
    }

    public function testInsertMicroseconds(): void
    {
        $schema = $this->schema(
            table: 'with_microseconds',
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]]
        );
        $schema->primary('id');
        $schema->datetime('datetime', 6);
        $schema->save();

        $expected = new \DateTimeImmutable();

        $id = $this->db(
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]]
        )->insert('with_microseconds')->values([
            'datetime' => $expected,
        ])->run();

        $result = $this->db(
            driverConfig: ['options' => ['withDatetimeMicroseconds' => true]]
        )->select('datetime')
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
