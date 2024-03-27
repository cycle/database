<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Schema\AbstractColumn;
use PHPUnit\Framework\TestCase;

final class AbstractColumnTest extends TestCase
{
    private AbstractColumn $column;

    protected function setUp(): void
    {
        $this->column = new class('foo', 'bar') extends AbstractColumn {};
    }

    public function testReadonlySchemaFalseByDefault(): void
    {
        $this->assertFalse($this->column->isReadonlySchema());
    }

    public function testReadonlySchema(): void
    {
        $this->assertFalse($this->column->isReadonlySchema());

        $this->column->setAttributes(['readonlySchema' => true]);

        $this->assertTrue($this->column->isReadonlySchema());
    }
}
