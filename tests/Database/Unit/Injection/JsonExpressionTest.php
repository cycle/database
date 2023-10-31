<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Injection;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Injection\JsonExpression;
use PHPUnit\Framework\TestCase;

final class JsonExpressionTest extends TestCase
{
    /**
     * @dataProvider parseArraySyntaxDataProvider
     */
    public function testParseArraySyntax(string $path, array $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'parseArraySyntax');
        $ref->setAccessible(true);

        $this->assertSame($ref->invoke($expression, $path), $expected);
    }

    /**
     * @dataProvider parseArraySyntaxInvalidDataProvider
     */
    public function testParseArraySyntaxWithInvalidPath(string $path): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'parseArraySyntax');
        $ref->setAccessible(true);

        $this->expectException(DriverException::class);
        $ref->invoke($expression, $path);
    }

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

        $this->assertSame('"options"', $ref->invoke($expression, $statement));
    }

    /**
     * @dataProvider wrapPathSegmentDataProvider
     */
    public function testWrapPathSegment(string $segment, string $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'wrapPathSegment');
        $ref->setAccessible(true);

        $this->assertSame($expected, $ref->invoke($expression, $segment));
    }

    /**
     * @dataProvider wrapPathDataProvider
     */
    public function testWrapPath(string $value, string $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'wrapPath');
        $ref->setAccessible(true);

        $this->assertSame(\sprintf("'%s'", $expected), $ref->invoke($expression, $value));
    }

    public function testDefaultQuotes(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getQuotes');
        $ref->setAccessible(true);

        $this->assertSame('""', $ref->invoke($expression));
    }

    public function testGetTokens(): void
    {
        $expression = $this->createExpression();

        $refExpression = new \ReflectionProperty($expression, 'expression');
        $refExpression->setAccessible(true);
        $refExpression->setValue($expression, 'foo');

        $refParameters = new \ReflectionProperty($expression, 'parameters');
        $refParameters->setAccessible(true);
        $refParameters->setValue($expression, ['bar']);


        $this->assertSame([
            'expression' => 'foo',
            'parameters' => ['bar'],
        ], $expression->getTokens());
    }

    public function testGetType(): void
    {
        $expression = $this->createExpression();

        $this->assertSame(CompilerInterface::JSON_EXPRESSION, $expression->getType());
    }

    public function testToString(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionProperty($expression, 'expression');
        $ref->setAccessible(true);
        $ref->setValue($expression, 'foo');

        $this->assertSame('exp:foo', (string) $expression);
    }

    public static function parseArraySyntaxDataProvider(): \Traversable
    {
        yield ['foo', ['foo']];
        yield ['foo[0]', ['foo', '0']];
        yield ['foo [0]', ['foo', '0']];
        yield ['foo[1]', ['foo', '1']];
        yield ['foo [1]', ['foo', '1']];
        yield ['foo[string-key]', ['foo', 'string-key']];
        yield ['foo [string-key]', ['foo', 'string-key']];
    }

    public static function parseArraySyntaxInvalidDataProvider(): \Traversable
    {
        yield ['foo[]'];
        yield ['foo[ ]'];
    }

    public static function wrapPathSegmentDataProvider(): \Traversable
    {
        yield ['foo', '"foo"'];
        yield ['foo[0]', '"foo"[0]'];
        yield ['foo [0]', '"foo"[0]'];
        yield ['foo[1]', '"foo"[1]'];
        yield ['foo [1]', '"foo"[1]'];
        yield ['foo[string-key]', '"foo"[string-key]'];
        yield ['foo [string-key]', '"foo"[string-key]'];
    }

    public static function wrapPathDataProvider(): \Traversable
    {
        yield ['options->languages', '$."options"."languages"'];
        yield ['options->languages[0]', '$."options"."languages"[0]'];
        yield ['phones', '$."phones"'];
        yield ['phones[1]', '$."phones"[1]'];
        yield ['phones[1]->numbers[3]', '$."phones"[1]."numbers"[3]'];
        yield ['phones [1]->numbers [3]', '$."phones"[1]."numbers"[3]'];
        yield ['options->languages[fr]', '$."options"."languages"[fr]'];
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

    private function createExpression(): JsonExpression
    {
        return new class () extends JsonExpression {
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
