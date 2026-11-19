<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLServer\Injection;

use Cycle\Database\Driver\SQLServer\Injection\CompileJsonContainsKey;
use Cycle\Database\Driver\SQLServer\Injection\CompileJsonDoesntContainKey;
use PHPUnit\Framework\TestCase;

/**
 * The last path segment is written into the SQL text, so it must stay inside a string literal.
 */
final class CompileJsonContainsKeyTest extends TestCase
{
    public static function provideStatements(): iterable
    {
        yield 'plain key' => [
            'settings->languages',
            "'languages' IN (SELECT [key] FROM openjson([settings]))",
        ];
        yield 'nested key' => [
            'settings->phones->work',
            "'work' IN (SELECT [key] FROM openjson([settings], '$.\"phones\"'))",
        ];
        yield 'key with an apostrophe' => [
            "settings->it's",
            "'it''s' IN (SELECT [key] FROM openjson([settings]))",
        ];
        yield 'key that tries to close the literal' => [
            "settings->x' IN ('y') OR 1=1 OR 'a",
            "'x'' IN (''y'') OR 1=1 OR ''a' IN (SELECT [key] FROM openjson([settings]))",
        ];
        yield 'integer array index' => [
            'settings->phones[1]',
            "1 IN (SELECT [key] FROM openjson([settings], '$.\"phones\"'))",
        ];
        yield 'nested integer array index' => [
            'settings->phones[1]->numbers[3]',
            "3 IN (SELECT [key] FROM openjson([settings], '$.\"phones\"[1].\"numbers\"'))",
        ];
        yield 'chained array indexes' => [
            'settings->matrix[1][2]',
            "2 IN (SELECT [key] FROM openjson([settings], '$.\"matrix\"[1]'))",
        ];
        yield 'non-integer array index' => [
            'settings->phones[0 IN (0) OR 1=1 OR 0]',
            "'0 IN (0) OR 1=1 OR 0' IN (SELECT [key] FROM openjson([settings], '$.\"phones\"'))",
        ];
        yield 'array index with an apostrophe' => [
            "settings->phones[it's]",
            "'it''s' IN (SELECT [key] FROM openjson([settings], '$.\"phones\"'))",
        ];
    }

    /**
     * @dataProvider provideStatements
     */
    public function testKeyStaysInsideStringLiteral(string $statement, string $expected): void
    {
        $this->assertSame($expected, (new CompileJsonContainsKey($statement))->getTokens()['expression']);
        $this->assertSame('NOT ' . $expected, (new CompileJsonDoesntContainKey($statement))->getTokens()['expression']);
    }
}
