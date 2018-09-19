<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Database\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\QueryCompiler;
use Spiral\Database\Injection\Expression;
use Spiral\Database\Injection\ExpressionInterface;
use Spiral\Database\Injection\FragmentInterface;

class ExpressionTest extends TestCase
{
    public function testExpression()
    {
        $expression = new Expression('expression');
        $this->assertInstanceOf(FragmentInterface::class, $expression);
        $this->assertInstanceOf(ExpressionInterface::class, $expression);

        $this->assertSame('expression', $expression->getExpression());
        $this->assertSame($expression->getExpression(), $expression->sqlStatement());

        //Compiler-less
        $this->assertSame($expression->sqlStatement(), (string)$expression);

        $compiler = m::mock(QueryCompiler::class);
        $compiler->shouldReceive('quote')->with('expression')->andReturn('"expression"');

        $this->assertSame('"expression"', $expression->sqlStatement($compiler));
    }
}