<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\MySQL;

use Cycle\Database\Driver\MySQL\MySQLOnConflict;
use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\QueryParameters;
use PHPUnit\Framework\TestCase;

class MySQLOnConflictTest extends TestCase
{
    public function testDefaultRowAlias(): void
    {
        $c = MySQLOnConflict::target('email');

        $this->assertSame(MySQLOnConflict::DEFAULT_ROW_ALIAS, $c->getRowAlias());
    }

    public function testWithRowAlias(): void
    {
        $c = MySQLOnConflict::target('email')->withRowAlias('updated');

        $this->assertSame('updated', $c->getRowAlias());
    }

    public function testWithRowAliasEmptyRejected(): void
    {
        $this->expectException(BuilderException::class);
        MySQLOnConflict::target('email')->withRowAlias('');
    }

    public function testWithRowAliasIsImmutable(): void
    {
        $base = MySQLOnConflict::target('email');
        $modified = $base->withRowAlias('updated');

        $this->assertSame(MySQLOnConflict::DEFAULT_ROW_ALIAS, $base->getRowAlias());
        $this->assertSame('updated', $modified->getRowAlias());
    }

    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $mysql = MySQLOnConflict::from($base);

        $this->assertInstanceOf(MySQLOnConflict::class, $mysql);
        $this->assertSame(['email'], $mysql->getTarget());
        $this->assertSame(MySQLOnConflict::DEFAULT_ROW_ALIAS, $mysql->getRowAlias());
    }

    public function testFromOtherDriverSubclassRejected(): void
    {
        $pg = PostgresOnConflict::target('email');

        $this->expectException(BuilderException::class);
        MySQLOnConflict::from($pg);
    }

    public function testCacheKeyIncludesRowAlias(): void
    {
        $a = MySQLOnConflict::target('email')->doUpdate(['name']);
        $b = MySQLOnConflict::target('email')->withRowAlias('custom')->doUpdate(['name']);

        $this->assertNotSame(
            $a->getCacheKey(new QueryParameters()),
            $b->getCacheKey(new QueryParameters()),
        );
    }
}
