<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Schema\AbstractColumn;
use PHPUnit\Framework\TestCase;

final class AbstractColumnTest
{
    private AbstractColumn $column;

    #[\Testo\Test]
    public function testReadonlySchemaFalseByDefault(): void
    {
        \Testo\Assert::false($this->column->isReadonlySchema());
    }

    #[\Testo\Test]
    public function testReadonlySchema(): void
    {
        \Testo\Assert::false($this->column->isReadonlySchema());

        $this->column->setAttributes(['readonlySchema' => true]);

        \Testo\Assert::true($this->column->isReadonlySchema());
    }

    #[\Testo\Lifecycle\BeforeTest]
    protected function setUp(): void
    {
        $this->column = new class('foo', 'bar') extends AbstractColumn {};
    }
}
