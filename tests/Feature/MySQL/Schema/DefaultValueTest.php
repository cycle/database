<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\DefaultValueTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class DefaultValueTest extends CommonClass
{
    #[Test]
    public function testTextDefaultValueString(): void
    {
        Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testTextDefaultValueString();
    }

    #[Test]
    public function testTextDefaultValueEmpty(): void
    {
        Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testTextDefaultValueEmpty();
    }

    #[Test]
    public function testJsonDefaultValueString(): void
    {
        Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testJsonDefaultValueString();
    }

    #[Test]
    public function testJsonDefaultValueArray(): void
    {
        Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testJsonDefaultValueArray();
    }

    #[Test]
    public function testJsonDefaultValueEmpty(): void
    {
        Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testJsonDefaultValueEmpty();
    }
}
