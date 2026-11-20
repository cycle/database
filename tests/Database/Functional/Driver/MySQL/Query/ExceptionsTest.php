<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

use Cycle\Database\Exception\StatementException;
// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\ExceptionsTest as CommonClass;
use Spiral\Database\Exception\StatementException\ConnectionException;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class ExceptionsTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testPacketsOutOfOrderConsideredAsConnectionException(): void
    {
        // Prepare connection to generate "Packets out of order. Expected 1 received 0. Packet size=145"
        // at the next query response
        $this->database->query('SET SESSION wait_timeout=1')->fetch();
        \usleep(1_300_000);

        try {
            $result = $this->database->query('SELECT version() AS version')->fetchAll();
            $this->assertNotEmpty($result[0]['version'] ?? '', 'Expected result from second query');
        } catch (\RuntimeException $e) {
            \Testo\Assert::instanceOf($e, ConnectionException::class);
            return;
        }
    }

    #[\Testo\Test]
    public function testCheckViolationIsAConstrainException(): void
    {
        $driver = $this->database->getDriver();

        // Raw SQL because the schema builder has no CHECK support.
        $driver->execute('CREATE TABLE test (id int PRIMARY KEY, pos int, CONSTRAINT c_pos CHECK (pos > 0))');

        \Testo\Expect::exception(StatementException\ConstrainException::class);

        $driver->execute('INSERT INTO test VALUES (1, -1)');
    }

    #[\Testo\Test]
    public function testMissingRequiredColumnIsAConstrainException(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute('CREATE TABLE test (id int PRIMARY KEY, value varchar(8) NOT NULL)');

        \Testo\Expect::exception(StatementException\ConstrainException::class);

        $driver->execute('INSERT INTO test (id) VALUES (1)');
    }
}
