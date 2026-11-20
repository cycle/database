<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Driver;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Driver\DriverTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class DriverTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testDDLResetTransactionLevel(): void
    {
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);

        $this->database->begin();
        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->save();

        \Testo\Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }
}
