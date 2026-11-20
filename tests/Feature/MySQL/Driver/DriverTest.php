<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Driver;

use Cycle\Database\Exception\DriverException;
use Cycle\Database\Tests\Feature\Common\Driver\DriverTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class DriverTest extends CommonClass
{
    #[Test]
    public function testDDLResetTransactionLevel(): void
    {
        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);

        $this->database->begin();
        Assert::same($this->database->getDriver()->getTransactionLevel(), 1);

        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->save();

        Assert::same($this->database->getDriver()->getTransactionLevel(), 0);
    }

    /**
     * The other drivers implement CursorInterface.
     */
    #[Test]
    public function testCursorThrowsByDefault(): void
    {
        Expect::exception(DriverException::class)->withMessageMatchingRegex('/cursors are not supported/i');

        // Database::cursor() checks CursorInterface and throws for drivers that don't implement it.
        \iterator_to_array(
            $this->database->cursor($this->database->select('1')),
        );
    }
}
