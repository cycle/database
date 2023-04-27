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
        $this->column = new class ('foo', 'bar') extends AbstractColumn {};
    }

    public function testReadOnlyFalseByDefault(): void
    {
        $this->assertFalse($this->column->isReadOnly());
    }

    public function testReadOnly(): void
    {
        $this->assertFalse($this->column->isReadOnly());

        $this->column->setAttributes(['readOnly' => true]);

        $this->assertTrue($this->column->isReadOnly());
    }
}
