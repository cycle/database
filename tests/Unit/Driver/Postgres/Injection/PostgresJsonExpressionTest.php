<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\Postgres\Injection;

use Cycle\Database\Driver\Postgres\Injection\PostgresJsonExpression;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Exception\DriverException;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
final class PostgresJsonExpressionTest
{
    public static function pathDataProvider(): \Traversable
    {
        yield ['options', ''];
        yield ['options->languages', ''];
        yield ['options->languages->fr', "'languages'"];
        yield ['options->personal->languages->fr', "'personal'->'languages'"];
    }

    public static function attributeDataProvider(): \Traversable
    {
        yield ['options->languages', "'languages'"];
        yield ['options->languages->fr', "'fr'"];
        yield ['options->personal->languages->fr', "'fr'"];
        yield ['options->personal->phones->3', 3];
    }

    #[DataProvider('pathDataProvider')]
    public function testGetPath(string $statement, string $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getPath');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression, $statement), $expected);
    }

    #[DataProvider('attributeDataProvider')]
    public function testGetAttribute(string $statement, string|int $expected): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getAttribute');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($expression, $statement), $expected);
    }

    public function testGetAttributeException(): void
    {
        $expression = $this->createExpression();
        $ref = new \ReflectionMethod($expression, 'getAttribute');
        $ref->setAccessible(true);

        Expect::exception(DriverException::class);
        $ref->invoke($expression, 'options');
    }

    private function createExpression(): PostgresJsonExpression
    {
        return new class extends PostgresJsonExpression {
            public function __construct()
            {
                $this->quoter = new Quoter('', $this->getQuotes());
            }

            protected function compile(string $statement): string {}
        };
    }
}
