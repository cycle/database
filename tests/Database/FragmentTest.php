<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Driver\SQLite\SQLiteCompiler;
use Spiral\Database\Injection\Expression;
use Spiral\Database\Injection\Fragment;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Query\QueryParameters;

class FragmentTest extends TestCase
{
    public function testFragment(): void
    {
        $fragment = new Fragment('some sql');
        $this->assertInstanceOf(FragmentInterface::class, $fragment);

        $q = new SQLiteCompiler();

        $this->assertSame(
            'some sql',
            $q->compile(new QueryParameters(), '', $fragment)
        );
    }

    public function testExpression(): void
    {
        $fragment = new Expression('some sql');
        $this->assertInstanceOf(FragmentInterface::class, $fragment);

        $q = new SQLiteCompiler();

        $this->assertSame(
            '"some" "sql"',
            $q->compile(new QueryParameters(), '', $fragment)
        );
    }

    public function testExpressionWithParameters(): void
    {
        $fragment = new Expression('name = ?', 123);

        $q = new SQLiteCompiler();

        $this->assertSame(
            '"name" = ?',
            $q->compile($p = new QueryParameters(), '', $fragment)
        );

        $this->assertSame(123, $p->getParameters()[0]->getValue());
    }

    public function testSetState(): void
    {
        $expression = new Expression('some sql');

        $exp = eval('return ' . var_export($expression, true) . ';');
        $this->assertSame(
            [
                'expression' => 'some sql',
                'parameters' => []
            ],
            $exp->getTokens()
        );

        $fragment = new Fragment('some sql');

        $f = eval('return ' . var_export($fragment, true) . ';');
        $this->assertSame(
            [
                'fragment' => 'some sql'
            ],
            $f->getTokens()
        );
    }
}
