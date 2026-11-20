<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Injection;

use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use PHPUnit\Framework\TestCase;

class FragmentTest
{
    #[\Testo\Test]
    public function testFragment(): void
    {
        $fragment = new Fragment('some sql');
        \Testo\Assert::instanceOf($fragment, FragmentInterface::class);

        $q = new SQLiteCompiler();

        \Testo\Assert::same($q->compile(new QueryParameters(), '', $fragment), 'some sql');
    }

    #[\Testo\Test]
    public function testExpression(): void
    {
        $fragment = new Expression('some sql');
        \Testo\Assert::instanceOf($fragment, FragmentInterface::class);

        $q = new SQLiteCompiler();

        \Testo\Assert::same($q->compile(new QueryParameters(), '', $fragment), '"some" "sql"');
    }

    #[\Testo\Test]
    public function testExpressionWithParameters(): void
    {
        $fragment = new Expression('name = ?', 123);

        $q = new SQLiteCompiler();

        \Testo\Assert::same($q->compile($p = new QueryParameters(), '', $fragment), '"name" = ?');

        \Testo\Assert::same($p->getParameters()[0]->getValue(), 123);
    }

    #[\Testo\Test]
    public function testSetState(): void
    {
        $expression = new Expression('some sql');

        $exp = eval('return ' . \var_export($expression, true) . ';');
        \Testo\Assert::same($exp->getTokens(), [
            'expression' => 'some sql',
            'parameters' => [],
        ]);

        $fragment = new Fragment('some sql');

        $f = eval('return ' . \var_export($fragment, true) . ';');
        \Testo\Assert::same($f->getTokens(), [
            'fragment' => 'some sql',
            'parameters' => [],
        ]);
    }
}
