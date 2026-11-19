<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\MySQL\Injection;

use Cycle\Database\Driver\MySQL\Injection\CompileJson;
use Cycle\Database\Driver\MySQL\Injection\MySQLJsonExpression;
use PHPUnit\Framework\TestCase;

final class MySQLJsonExpressionTest extends TestCase
{
    public static function provideStatements(): iterable
    {
        yield 'plain key' => ['data->lang', "json_unquote(json_extract(`data`, '$.\"lang\"'))"];
        yield 'apostrophe' => ["data->it's", "json_unquote(json_extract(`data`, '$.\"it''s\"'))"];
        yield 'backslash' => [
            'data->a\\b',
            "json_unquote(json_extract(`data`, _utf8mb4 X'" . \bin2hex('$."a\\\\b"') . "'))",
        ];
        yield 'backslash before an apostrophe' => [
            "data->own\\'",
            "json_unquote(json_extract(`data`, _utf8mb4 X'" . \bin2hex('$."own\\\\\'"') . "'))",
        ];
        yield 'line feed' => [
            "data->a\nb",
            "json_unquote(json_extract(`data`, _utf8mb4 X'" . \bin2hex('$."a\nb"') . "'))",
        ];
        yield 'double quote' => [
            'data->a"b',
            "json_unquote(json_extract(`data`, _utf8mb4 X'" . \bin2hex('$."a\\"b"') . "'))",
        ];
    }

    public function testGetQuotes(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getQuotes');
        $ref->setAccessible(true);

        $this->assertSame('``', $ref->invoke($expression));
    }

    /**
     * @dataProvider provideStatements
     */
    public function testPathLiteralCannotBeEndedEarly(string $statement, string $expected): void
    {
        $this->assertSame($expected, (new CompileJson($statement))->getTokens()['expression']);
    }

    private function createExpression(): MySQLJsonExpression
    {
        return new class extends MySQLJsonExpression {
            public function __construct() {}

            protected function compile(string $statement): string {}
        };
    }
}
