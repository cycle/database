<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\ExceptionsTest as CommonClass;
use Spiral\Database\Exception\StatementException\ConnectionException;

/**
 * @group driver
 * @group driver-mysql
 */
class ExceptionsTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testPacketsOutOfOrderConsideredAsConnectionException(): void
    {
        if (PHP_VERSION_ID < 70400) {
            $this->markTestSkipped('Expecting PHP version >=7.4. Skipped due to '.PHP_VERSION);
        }

        // Prepare connection to generate "Packets out of order. Expected 1 received 0. Packet size=145"
        // at the next query response
        $this->database->query('SET SESSION wait_timeout=1')->fetch();
        sleep(1);

        try {
            $result = $this->database->query('SELECT version() AS version')->fetchAll();
            $this->assertNotEmpty($result[0]['version'] ?? '', 'Expected result from second query');
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf(ConnectionException::class, $e);

            return;
        }
    }
}
