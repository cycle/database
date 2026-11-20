<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\ReadonlyHandler;
use Cycle\Database\Schema\AbstractTable;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ReadonlyHandlerTest
{
    /**
     * When the wrapped handler does not implement BulkSchemaProviderInterface (e.g. a third-party
     * handler), getSchemas() must still work by falling back to a per-table getSchema() loop rather
     * than failing.
     */
    public function testGetSchemasFallsBackToPerTableWhenParentIsNotBulkProvider(): void
    {
        $tableA = \Mockery::mock(AbstractTable::class)->shouldIgnoreMissing();
        $tableB = \Mockery::mock(AbstractTable::class)->shouldIgnoreMissing();

        $parent = \Mockery::mock(HandlerInterface::class)->shouldIgnoreMissing();
        $parent->shouldReceive('getSchema')->andReturnUsing(static fn(...$args) => \array_column(\array_filter([
            ['a', 'pre_', $tableA],
            ['b', 'pre_', $tableB],
        ], static fn($row) => \array_slice($row, 0, -1) === $args), \count($args))[0] ?? null);

        $handler = new ReadonlyHandler($parent);

        Assert::same($handler->getSchemas(['a', 'b'], 'pre_'), ['a' => $tableA, 'b' => $tableB]);
    }
}
