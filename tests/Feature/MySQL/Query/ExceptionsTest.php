<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Query;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Feature\Common\Query\ExceptionsTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Spiral\Database\Exception\StatementException\ConnectionException;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class ExceptionsTest extends CommonClass
{
    #[Test]
    public function testPacketsOutOfOrderConsideredAsConnectionException(): void
    {
        // Prepare connection to generate "Packets out of order. Expected 1 received 0. Packet size=145"
        // at the next query response
        $this->database->query('SET SESSION wait_timeout=1')->fetch();
        \usleep(1_300_000);

        try {
            $result = $this->database->query('SELECT version() AS version')->fetchAll();
            Assert::notBlank($result[0]['version'] ?? '', 'Expected result from second query');
        } catch (\RuntimeException $e) {
            Assert::instanceOf($e, ConnectionException::class);
            return;
        }
    }

    #[Test]
    public function testCheckViolationIsAConstrainException(): void
    {
        $driver = $this->database->getDriver();

        // Raw SQL because the schema builder has no CHECK support.
        $driver->execute('CREATE TABLE test (id int PRIMARY KEY, pos int, CONSTRAINT c_pos CHECK (pos > 0))');

        Expect::exception(StatementException\ConstrainException::class);

        $driver->execute('INSERT INTO test VALUES (1, -1)');
    }

    #[Test]
    public function testMissingRequiredColumnIsAConstrainException(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute('CREATE TABLE test (id int PRIMARY KEY, value varchar(8) NOT NULL)');

        Expect::exception(StatementException\ConstrainException::class);

        $driver->execute('INSERT INTO test (id) VALUES (1)');
    }
}
