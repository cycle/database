<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Injection\Fragment;
use Cycle\Database\Tests\Feature\Common\Schema\DefaultValueTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
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

    #[Test]
    public function testBitDefaultIsReadAsFragment(): void
    {
        $this->database->execute("CREATE TABLE `table` (id INT PRIMARY KEY, flags BIT(4) DEFAULT b'101')");

        $default = $this->schema('table')->column('flags')->getDefaultValue();

        Assert::instanceOf($default, Fragment::class);
        Assert::same((string) $default, "b'101'");
    }

    #[Test]
    public function testZeroTimestampDefaultIsReadAsEpoch(): void
    {
        $this->database->execute("SET SESSION sql_mode = ''");
        try {
            $this->database->execute(
                "CREATE TABLE `table` (id INT PRIMARY KEY, at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00')",
            );
        } finally {
            $this->database->execute('SET SESSION sql_mode = DEFAULT');
        }

        $default = $this->schema('table')->column('at')->getDefaultValue();

        Assert::instanceOf($default, \DateTimeInterface::class);
        Assert::same($default->getTimestamp(), 0);
    }

    #[Test]
    public function testMariaDbNowDefaultIsTheCurrentTimestamp(): void
    {
        $column = $this->schema('table')->datetime('at')->defaultValue('current_timestamp()');

        Assert::equals($column->getDefaultValue(), new Fragment('CURRENT_TIMESTAMP'));
    }
}
