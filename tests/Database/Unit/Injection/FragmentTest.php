<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Injection;

use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use Testo\Assert;
use Testo\Test;

#[Test]
final class FragmentTest
{
    public function testFragment(): void
    {
        $fragment = new Fragment('some sql');
        Assert::instanceOf($fragment, FragmentInterface::class);

        $q = new SQLiteCompiler();

        Assert::same($q->compile(new QueryParameters(), '', $fragment), 'some sql');
    }

    public function testExpression(): void
    {
        $fragment = new Expression('some sql');
        Assert::instanceOf($fragment, FragmentInterface::class);

        $q = new SQLiteCompiler();

        Assert::same($q->compile(new QueryParameters(), '', $fragment), '"some" "sql"');
    }

    public function testExpressionWithParameters(): void
    {
        $fragment = new Expression('name = ?', 123);

        $q = new SQLiteCompiler();

        Assert::same($q->compile($p = new QueryParameters(), '', $fragment), '"name" = ?');

        Assert::same($p->getParameters()[0]->getValue(), 123);
    }

    public function testSetState(): void
    {
        $expression = new Expression('some sql');

        $exp = eval('return ' . \var_export($expression, true) . ';');
        Assert::same($exp->getTokens(), [
            'expression' => 'some sql',
            'parameters' => [],
        ]);

        $fragment = new Fragment('some sql');

        $f = eval('return ' . \var_export($fragment, true) . ';');
        Assert::same($f->getTokens(), [
            'fragment' => 'some sql',
            'parameters' => [],
        ]);
    }
}
