<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\Compiler;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Query\QueryParameters;
use Testo\Assert;
use Testo\Test;

#[Test]
final class CompilerTest
{
    public function testCompileJsonOrderByShouldReturnOriginalStatement(): void
    {
        $compiler = new class extends Compiler {
            protected function limit(QueryParameters $params, Quoter $q, ?int $limit = null, ?int $offset = null): string {}
        };

        $ref = new \ReflectionMethod($compiler, 'compileJsonOrderBy');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($compiler, 'foo-bar'), 'foo-bar');
    }
}
