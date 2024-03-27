<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\MySQL\Injection;

use Cycle\Database\Driver\MySQL\Injection\MySQLJsonExpression;
use PHPUnit\Framework\TestCase;

final class MySQLJsonExpressionTest extends TestCase
{
    public function testGetQuotes(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getQuotes');
        $ref->setAccessible(true);

        $this->assertSame('``', $ref->invoke($expression));
    }

    private function createExpression(): MySQLJsonExpression
    {
        return new class() extends MySQLJsonExpression {
            public function __construct()
            {
            }

            protected function compile(string $statement): string
            {
            }
        };
    }
}
