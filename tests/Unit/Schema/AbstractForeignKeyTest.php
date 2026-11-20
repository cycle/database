<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLForeignKey;
use Cycle\Database\Schema\AbstractForeignKey;
use Testo\Assert;
use Testo\Test;

#[Test]
final class AbstractForeignKeyTest
{
    public function testDefaultCreateIndex(): void
    {
        $fk = new class('foo', 'bar', 'baz') extends AbstractForeignKey {};

        Assert::true($fk->hasIndex());
    }

    public function testWithoutIndex(): void
    {
        $fk = new class('foo', 'bar', 'baz') extends AbstractForeignKey {};
        $fk->setIndex(false);

        Assert::false($fk->hasIndex());
    }

    public function testMySQLShouldAlwaysCreateIndex(): void
    {
        $fk = new MySQLForeignKey('foo', 'bar', 'baz');
        $fk->setIndex(false);

        Assert::true($fk->hasIndex());
    }
}
