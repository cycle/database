<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\DriverTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class DriverTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testDDLResetTransactionLevel(): void
    {
        $this->assertSame(0, $this->database->getDriver()->getTransactionLevel());

        $this->database->begin();
        $this->assertSame(1, $this->database->getDriver()->getTransactionLevel());

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->save();

        $this->assertSame(0, $this->database->getDriver()->getTransactionLevel());
    }
}
