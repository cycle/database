<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Driver;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class DriverTest extends BaseTest
{
    public function testTransactionLevel(): void
    {
        $this->assertSame(0, $this->database->getDriver()->getTransactionLevel());

        $this->database->begin();
        $this->assertSame(1, $this->database->getDriver()->getTransactionLevel());
        $this->database->begin();
        $this->assertSame(2, $this->database->getDriver()->getTransactionLevel());
        $this->database->begin();
        $this->assertSame(3, $this->database->getDriver()->getTransactionLevel());

        $this->database->rollback();
        $this->assertSame(2, $this->database->getDriver()->getTransactionLevel());
        $this->database->rollback();
        $this->assertSame(1, $this->database->getDriver()->getTransactionLevel());
        $this->database->rollback();
        $this->assertSame(0, $this->database->getDriver()->getTransactionLevel());
    }
}
