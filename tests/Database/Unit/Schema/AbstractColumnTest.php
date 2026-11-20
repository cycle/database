<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Schema\AbstractColumn;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class AbstractColumnTest
{
    private AbstractColumn $column;

    public function testReadonlySchemaFalseByDefault(): void
    {
        Assert::false($this->column->isReadonlySchema());
    }

    public function testReadonlySchema(): void
    {
        Assert::false($this->column->isReadonlySchema());

        $this->column->setAttributes(['readonlySchema' => true]);

        Assert::true($this->column->isReadonlySchema());
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->column = new class('foo', 'bar') extends AbstractColumn {};
    }
}
