<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class StringColumnTest extends BaseTest
{
    #[\Testo\Test]
    public function testStringDefaultSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->string('column');
        $schema->save();
        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($column->getSize(), 255);
    }

    #[\Testo\Test]
    public function testStringDefaultSizeViaMagicMethod(): void
    {
        $schema = $this->schema('table');

        $column = $schema->column('column')->__call('string');
        $schema->save();
        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($column->getSize(), 255);
    }

    #[\Testo\Test]
    public function testStringSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->string('column', size: 64);
        $schema->save();
        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($column->getSize(), 64);
    }
}
