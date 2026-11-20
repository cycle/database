<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\MySQL;

use Cycle\Database\Driver\MySQL\MySQLOnConflict;
use Cycle\Database\Driver\Postgres\PostgresOnConflict;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\QueryParameters;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Expect;
use Testo\Test;

#[Test]
final class MySQLOnConflictTest
{
    public function testDefaultRowAlias(): void
    {
        $c = MySQLOnConflict::target('email');

        Assert::same($c->getRowAlias(), MySQLOnConflict::DEFAULT_ROW_ALIAS);
    }

    public function testWithRowAlias(): void
    {
        $c = MySQLOnConflict::target('email')->withRowAlias('updated');

        Assert::same($c->getRowAlias(), 'updated');
    }

    #[ExpectException(BuilderException::class)]
    public function testWithRowAliasEmptyRejected(): void
    {
        MySQLOnConflict::target('email')->withRowAlias('');
    }

    public function testWithRowAliasIsImmutable(): void
    {
        $base = MySQLOnConflict::target('email');
        $modified = $base->withRowAlias('updated');

        Assert::same($base->getRowAlias(), MySQLOnConflict::DEFAULT_ROW_ALIAS);
        Assert::same($modified->getRowAlias(), 'updated');
    }

    public function testFromBase(): void
    {
        $base = OnConflict::target('email')->doUpdate(['name']);
        $mysql = MySQLOnConflict::from($base);

        Assert::instanceOf($mysql, MySQLOnConflict::class);
        Assert::same($mysql->getTarget(), ['email']);
        Assert::same($mysql->getRowAlias(), MySQLOnConflict::DEFAULT_ROW_ALIAS);
    }

    public function testFromOtherDriverSubclassRejected(): void
    {
        $pg = PostgresOnConflict::target('email');

        Expect::exception(BuilderException::class);
        MySQLOnConflict::from($pg);
    }

    public function testCacheKeyIncludesRowAlias(): void
    {
        $a = MySQLOnConflict::target('email')->doUpdate(['name']);
        $b = MySQLOnConflict::target('email')->withRowAlias('custom')->doUpdate(['name']);

        Assert::notSame($b->getCacheKey(new QueryParameters()), $a->getCacheKey(new QueryParameters()));
    }
}
