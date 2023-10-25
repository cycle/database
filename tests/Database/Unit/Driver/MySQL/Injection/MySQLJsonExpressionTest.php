<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\MySQL\Injection;

use Cycle\Database\Driver\MySQL\Injection\MySQLJsonExpression;
use Cycle\Database\Driver\Quoter;
use PHPUnit\Framework\TestCase;

final class MySQLJsonExpressionTest extends TestCase
{
    /**
     * @dataProvider pathDataProvider
     */
    public function testGetPath(string $statement, string $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getPath');
        $ref->setAccessible(true);

        $this->assertSame($expected, $ref->invoke($expression, $statement));
    }

    /**
     * @dataProvider fieldDataProvider
     */
    public function testGetField(string $statement): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getField');
        $ref->setAccessible(true);

        $this->assertSame('`options`', $ref->invoke($expression, $statement));
    }

    public function testGetQuotes(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getQuotes');
        $ref->setAccessible(true);

        $this->assertSame('``', $ref->invoke($expression));
    }

    public static function pathDataProvider(): \Traversable
    {
        yield ['options', ''];
        yield ['options->languages', ', \'$."languages"\''];
        yield ['options->languages->fr', ', \'$."languages"."fr"\''];
    }

    public static function fieldDataProvider(): \Traversable
    {
        yield ['options'];
        yield ['options->languages'];
        yield ['options->languages->fr'];
    }

    private function createExpression(): MySQLJsonExpression
    {
        return new class () extends MySQLJsonExpression {
            public function __construct()
            {
                $this->quoter = new Quoter('', $this->getQuotes());
            }

            protected function compile(string $statement): string
            {
            }
        };
    }
}
