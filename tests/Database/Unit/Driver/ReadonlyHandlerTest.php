<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\ReadonlyHandler;
use Cycle\Database\Schema\AbstractTable;
use PHPUnit\Framework\TestCase;

final class ReadonlyHandlerTest extends TestCase
{
    /**
     * When the wrapped handler does not implement BulkSchemaProviderInterface (e.g. a third-party
     * handler), getSchemas() must still work by falling back to a per-table getSchema() loop rather
     * than failing.
     */
    public function testGetSchemasFallsBackToPerTableWhenParentIsNotBulkProvider(): void
    {
        $tableA = $this->createMock(AbstractTable::class);
        $tableB = $this->createMock(AbstractTable::class);

        $parent = $this->createMock(HandlerInterface::class);
        $parent->method('getSchema')->willReturnMap([
            ['a', 'pre_', $tableA],
            ['b', 'pre_', $tableB],
        ]);

        $handler = new ReadonlyHandler($parent);

        $this->assertSame(
            ['a' => $tableA, 'b' => $tableB],
            $handler->getSchemas(['a', 'b'], 'pre_'),
        );
    }
}
