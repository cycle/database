<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\Compiler;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Query\QueryParameters;
use PHPUnit\Framework\TestCase;

final class CompilerTest extends TestCase
{
    public function testCompileJsonOrderByShouldReturnOriginalStatement(): void
    {
        $compiler = new class () extends Compiler {
            protected function limit(QueryParameters $params, Quoter $q, int $limit = null, int $offset = null): string
            {
            }
        };

        $ref = new \ReflectionMethod($compiler, 'compileJsonOrderBy');
        $ref->setAccessible(true);

        $this->assertSame('foo-bar', $ref->invoke($compiler, 'foo-bar'));
    }
}
