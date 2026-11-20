<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\SQLServer\Injection;

use Cycle\Database\Driver\SQLServer\Injection\CompileJsonContainsKey;
use Cycle\Database\Driver\SQLServer\Injection\CompileJsonDoesntContainKey;
use PHPUnit\Framework\TestCase;

/**
 * The last path segment is written into the SQL text, so it must stay inside a string literal.
 */
final class CompileJsonContainsKeyTest
{
    public static function provideStatements(): iterable
    {
        yield 'plain key' => [
            'settings->languages',
            "N'languages' IN (SELECT [key] FROM openjson([settings]))",
        ];
        yield 'nested key' => [
            'settings->phones->work',
            "N'work' IN (SELECT [key] FROM openjson([settings], N'$.\"phones\"'))",
        ];
        yield 'key with an apostrophe' => [
            "settings->it's",
            "N'it''s' IN (SELECT [key] FROM openjson([settings]))",
        ];
        yield 'key that tries to close the literal' => [
            "settings->x' IN ('y') OR 1=1 OR 'a",
            "N'x'' IN (''y'') OR 1=1 OR ''a' IN (SELECT [key] FROM openjson([settings]))",
        ];
        yield 'array index' => [
            'settings->phones[1]',
            "N'1' IN (SELECT [key] FROM openjson([settings], N'$.\"phones\"'))",
        ];
        yield 'nested array index' => [
            'settings->phones[1]->numbers[3]',
            "N'3' IN (SELECT [key] FROM openjson([settings], N'$.\"phones\"[1].\"numbers\"'))",
        ];
        yield 'chained array indexes' => [
            'settings->matrix[1][2]',
            "N'2' IN (SELECT [key] FROM openjson([settings], N'$.\"matrix\"[1]'))",
        ];
        yield 'array index that tries to close the literal' => [
            'settings->phones[0 IN (0) OR 1=1 OR 0]',
            "N'0 IN (0) OR 1=1 OR 0' IN (SELECT [key] FROM openjson([settings], N'$.\"phones\"'))",
        ];
        yield 'array index with an apostrophe' => [
            "settings->phones[it's]",
            "N'it''s' IN (SELECT [key] FROM openjson([settings], N'$.\"phones\"'))",
        ];
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('provideStatements')]
    public function testKeyStaysInsideStringLiteral(string $statement, string $expected): void
    {
        \Testo\Assert::same((new CompileJsonContainsKey($statement))->getTokens()['expression'], $expected);
        \Testo\Assert::same((new CompileJsonDoesntContainKey($statement))->getTokens()['expression'], 'NOT ' . $expected);
    }
}
