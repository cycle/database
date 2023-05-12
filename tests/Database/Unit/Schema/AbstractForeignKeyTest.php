<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLForeignKey;
use Cycle\Database\Schema\AbstractForeignKey;
use PHPUnit\Framework\TestCase;

final class AbstractForeignKeyTest extends TestCase
{
    public function testDefaultCreateIndex(): void
    {
        $fk = new class ('foo', 'bar', 'baz') extends AbstractForeignKey {};

        $this->assertTrue($fk->hasIndex());
    }

    public function testWithoutIndex(): void
    {
        $fk = new class ('foo', 'bar', 'baz') extends AbstractForeignKey {};
        $fk->setIndex(false);

        $this->assertFalse($fk->hasIndex());
    }

    public function testMySQLShouldAlwaysCreateIndex(): void
    {
        $fk = new MySQLForeignKey('foo', 'bar', 'baz');
        $fk->setIndex(false);

        $this->assertTrue($fk->hasIndex());
    }
}
