<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Handler;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\ComparatorInterface;
use Cycle\Database\Schema\Reflector;
use PHPUnit\Framework\TestCase;

final class ReflectorTest extends TestCase
{
    private Reflector $reflector;
    private AbstractTable $table;

    protected function setUp(): void
    {
        $this->table = $this->createMock(AbstractTable::class);

        $comparator = $this->createMock(ComparatorInterface::class);
        $comparator
            ->expects($this->once())
            ->method('hasChanges')
            ->willReturn(true);

        $this->table
            ->expects($this->exactly(2))
            ->method('getFullName')
            ->willReturn('foo');
        $this->table
            ->expects($this->once())
            ->method('getComparator')
            ->willReturn($comparator);

        $this->reflector = new Reflector();
    }

    public function testMethodBeforeSyncShouldBeCalled(): void
    {
        $handler = $this->createMock(Handler::class);
        $driver = $this->createMock(DriverInterface::class);

        $this->table
            ->expects($this->exactly(4))
            ->method('getDriver')
            ->willReturn($driver);

        $handler
            ->expects($this->once())
            ->method('beforeSync')
            ->with(['foo' => $this->table]);
        $handler
            ->expects($this->once())
            ->method('afterSync')
            ->with(['foo' => $this->table]);

        $driver
            ->expects($this->exactly(2))
            ->method('getSchemaHandler')
            ->willReturn($handler);

        $this->reflector->addTable($this->table);
        $this->reflector->run();
    }

    public function testMethodBeforeSyncShouldNotBeCalledIfNotExists(): void
    {
        $handler = $this->createMock(HandlerInterface::class);
        $driver = $this->createMock(DriverInterface::class);

        $this->table
            ->expects($this->exactly(4))
            ->method('getDriver')
            ->willReturn($driver);

        $driver
            ->expects($this->exactly(2))
            ->method('getSchemaHandler')
            ->willReturn($handler);

        $this->reflector->addTable($this->table);
        $this->reflector->run();
    }
}
