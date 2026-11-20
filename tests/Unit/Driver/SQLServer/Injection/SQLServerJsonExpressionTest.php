<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLServer\Injection;

use Cycle\Database\Driver\SQLServer\Injection\SQLServerJsonExpression;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SQLServerJsonExpressionTest
{
    public function testGetQuotes(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getQuotes');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression), '[]');
    }

    private function createExpression(): SQLServerJsonExpression
    {
        return new class extends SQLServerJsonExpression {
            public function __construct() {}

            protected function compile(string $statement): string {}
        };
    }
}
