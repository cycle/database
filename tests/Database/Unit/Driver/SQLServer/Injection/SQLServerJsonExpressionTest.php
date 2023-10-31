<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLServer\Injection;

use Cycle\Database\Driver\SQLServer\Injection\SQLServerJsonExpression;
use PHPUnit\Framework\TestCase;

final class SQLServerJsonExpressionTest extends TestCase
{
    public function testGetQuotes(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getQuotes');
        $ref->setAccessible(true);

        $this->assertSame('[]', $ref->invoke($expression));
    }

    private function createExpression(): SQLServerJsonExpression
    {
        return new class () extends SQLServerJsonExpression {
            public function __construct()
            {
            }

            protected function compile(string $statement): string
            {
            }
        };
    }
}
