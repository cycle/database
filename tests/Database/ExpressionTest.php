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
use Spiral\Database\Driver\Compiler;
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
        $this->assertSame($expression->getExpression(), $expression->compile());

        //Compiler-less
        $this->assertSame($expression->compile(), (string)$expression);

        $compiler = m::mock(Compiler::class);
        $compiler->shouldReceive('quote')->with('expression')->andReturn('"expression"');

        $this->assertSame('"expression"', $expression->compile($compiler));
    }
}