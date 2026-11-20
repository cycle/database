<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Injection;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Driver\SQLite\Injection\CompileJson;
use Cycle\Database\Injection\JsonExpression;
use Cycle\Database\Injection\Parameter;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
final class JsonExpressionTest
{
    public static function parseArraySyntaxDataProvider(): \Traversable
    {
        yield ['foo', ['foo']];
        yield ['foo[0]', ['foo', '0']];
        yield ['foo [0]', ['foo', '0']];
        yield ['foo[1]', ['foo', '1']];
        yield ['foo [1]', ['foo', '1']];
        yield ['foo[string-key]', ['foo', 'string-key']];
        yield ['foo [string-key]', ['foo', 'string-key']];
        yield ['foo[1][2]', ['foo', '1', '2']];
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
        yield ['a"b', '"a\"b"'];
        yield ['a\\b', '"a\\\\b"'];
        yield ['a"."b[0]', '"a\".\"b"[0]'];
        yield ["a\nb", '"a\nb"'];
        yield ['ключ/x', '"ключ/x"'];
        yield ['foo[1][2]', '"foo"[1][2]'];
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
        yield ['matrix[1][2]->cell', '$."matrix"[1][2]."cell"'];
        yield ["options->it's", '$."options"."it\'\'s"'];
        yield ["options->a+'b", '$."options"."a+\'\'b"'];
        yield ["options->phones[it's]", '$."options"."phones"[it\'\'s]'];
    }

    public static function quoteStringLiteralDataProvider(): \Traversable
    {
        yield ['plain', "'", "'plain'"];
        yield ["it's", "'", "'it''s'"];
        yield ["a+'b", "'", "'a+''b'"];
        yield ["''", "'", "''''''"];
        yield ['a"b', '"', '"a""b"'];
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

    #[DataProvider('parseArraySyntaxDataProvider')]
    public function testParseArraySyntax(string $path, array $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'parseArraySyntax');
        $ref->setAccessible(true);

        Assert::same($expected, $ref->invoke($expression, $path));
    }

    #[DataProvider('parseArraySyntaxInvalidDataProvider')]
    public function testParseArraySyntaxWithInvalidPath(string $path): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'parseArraySyntax');
        $ref->setAccessible(true);

        Expect::exception(DriverException::class);
        $ref->invoke($expression, $path);
    }

    #[DataProvider('pathDataProvider')]
    public function testGetPath(string $statement, string $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getPath');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression, $statement), $expected);
    }

    #[DataProvider('fieldDataProvider')]
    public function testGetField(string $statement): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getField');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression, $statement), '"options"');
    }

    #[DataProvider('wrapPathSegmentDataProvider')]
    public function testWrapPathSegment(string $segment, string $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'wrapPathSegment');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression, $segment), $expected);
    }

    #[DataProvider('wrapPathDataProvider')]
    public function testWrapPath(string $value, string $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'wrapPath');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression, $value), \sprintf("'%s'", $expected));
    }

    #[DataProvider('quoteStringLiteralDataProvider')]
    public function testQuoteStringLiteral(string $value, string $quote, string $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'quoteStringLiteral');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression, $value, $quote), $expected);
    }

    public function testDefaultQuotes(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getQuotes');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression), '""');
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


        Assert::same($expression->getTokens(), [
            'expression' => 'foo',
            'parameters' => ['bar'],
        ]);
    }

    public function testGetType(): void
    {
        $expression = $this->createExpression();

        Assert::same($expression->getType(), CompilerInterface::JSON_EXPRESSION);
    }

    public function testToString(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionProperty($expression, 'expression');
        $ref->setAccessible(true);
        $ref->setValue($expression, 'foo');

        Assert::same((string) $expression, 'exp:foo');
    }

    public function restoresFromExportedStateKeepingAGivenParameterInstance(): void
    {
        $parameter = new Parameter(1);

        $expression = CompileJson::__set_state(['statement' => 'a->b', 'parameters' => [$parameter]]);

        Assert::same($expression->getTokens(), (new CompileJson('a->b', $parameter))->getTokens());
        Assert::same($expression->getTokens()['parameters'], [$parameter]);
    }

    private function createExpression(): JsonExpression
    {
        return new class extends JsonExpression {
            public function __construct()
            {
                $this->quoter = new Quoter('', $this->getQuotes());
            }

            protected function compile(string $statement): string {}
        };
    }
}
