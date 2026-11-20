<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\MySQL;

use Cycle\Database\Driver\MySQL\MySQLOnConflict;
use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\QueryParameters;
use PHPUnit\Framework\TestCase;

class MySQLOnConflictTest
{
    #[\Testo\Test]
    public function testDefaultRowAlias(): void
    {
        $c = MySQLOnConflict::target('email');

        \Testo\Assert::same($c->getRowAlias(), MySQLOnConflict::DEFAULT_ROW_ALIAS);
    }

    #[\Testo\Test]
    public function testWithRowAlias(): void
    {
        $c = MySQLOnConflict::target('email')->withRowAlias('updated');

        \Testo\Assert::same($c->getRowAlias(), 'updated');
    }

    #[\Testo\Test]
    public function testWithRowAliasEmptyRejected(): void
    {
        \Testo\Expect::exception(BuilderException::class);
        MySQLOnConflict::target('email')->withRowAlias('');
    }

    #[\Testo\Test]
    public function testWithRowAliasIsImmutable(): void
    {
        $base = MySQLOnConflict::target('email');
        $modified = $base->withRowAlias('updated');

        \Testo\Assert::same($base->getRowAlias(), MySQLOnConflict::DEFAULT_ROW_ALIAS);
        \Testo\Assert::same($modified->getRowAlias(), 'updated');
    }

    #[\Testo\Test]
    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $mysql = MySQLOnConflict::from($base);

        \Testo\Assert::instanceOf($mysql, MySQLOnConflict::class);
        \Testo\Assert::same($mysql->getTarget(), ['email']);
        \Testo\Assert::same($mysql->getRowAlias(), MySQLOnConflict::DEFAULT_ROW_ALIAS);
    }

    #[\Testo\Test]
    public function testFromOtherDriverSubclassRejected(): void
    {
        $pg = PostgresOnConflict::target('email');

        \Testo\Expect::exception(BuilderException::class);
        MySQLOnConflict::from($pg);
    }

    #[\Testo\Test]
    public function testCacheKeyIncludesRowAlias(): void
    {
        $a = MySQLOnConflict::target('email')->doUpdate(['name']);
        $b = MySQLOnConflict::target('email')->withRowAlias('custom')->doUpdate(['name']);

        \Testo\Assert::notSame($b->getCacheKey(new QueryParameters()), $a->getCacheKey(new QueryParameters()));
    }
}
