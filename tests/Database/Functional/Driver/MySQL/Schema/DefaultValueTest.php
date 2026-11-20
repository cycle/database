<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DefaultValueTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class DefaultValueTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testTextDefaultValueString(): void
    {
        \Testo\Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testTextDefaultValueString();
    }

    #[\Testo\Test]
    public function testTextDefaultValueEmpty(): void
    {
        \Testo\Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testTextDefaultValueEmpty();
    }

    #[\Testo\Test]
    public function testJsonDefaultValueString(): void
    {
        \Testo\Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testJsonDefaultValueString();
    }

    #[\Testo\Test]
    public function testJsonDefaultValueArray(): void
    {
        \Testo\Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testJsonDefaultValueArray();
    }

    #[\Testo\Test]
    public function testJsonDefaultValueEmpty(): void
    {
        \Testo\Expect::exception(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class)->withMessageContaining('Column table.target of type text/blob/json can not have non empty default value');
        parent::testJsonDefaultValueEmpty();
    }
}
