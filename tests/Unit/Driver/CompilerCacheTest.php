<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\CompilerCache;
use Cycle\Database\Driver\Postgres\PostgresCompiler;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\QueryParameters;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CompilerCacheTest
{
    public function testHashInsertQueryWithReturningFragment(): void
    {
        $compiler = new CompilerCache(new PostgresCompiler());
        $ref = new \ReflectionMethod($compiler, 'hashInsertQuery');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($compiler, new QueryParameters(), [
            'table' => 'some_table',
            'columns' => ['name', 'full_name'],
            'values' => ['Foo'],
            'return' => ['name', new Fragment('"full_name" as "fullName"')],
        ]), 'i_some_tablename_full_name_rname_"full_name" as "fullName"P?');
    }
}
