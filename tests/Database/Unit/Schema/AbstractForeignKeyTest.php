<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLForeignKey;
use Cycle\Database\Schema\AbstractForeignKey;
use PHPUnit\Framework\TestCase;

final class AbstractForeignKeyTest
{
    #[\Testo\Test]
    public function testDefaultCreateIndex(): void
    {
        $fk = new class('foo', 'bar', 'baz') extends AbstractForeignKey {};

        \Testo\Assert::true($fk->hasIndex());
    }

    #[\Testo\Test]
    public function testWithoutIndex(): void
    {
        $fk = new class('foo', 'bar', 'baz') extends AbstractForeignKey {};
        $fk->setIndex(false);

        \Testo\Assert::false($fk->hasIndex());
    }

    #[\Testo\Test]
    public function testMySQLShouldAlwaysCreateIndex(): void
    {
        $fk = new MySQLForeignKey('foo', 'bar', 'baz');
        $fk->setIndex(false);

        \Testo\Assert::true($fk->hasIndex());
    }
}
