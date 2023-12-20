<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class StringColumnTest extends BaseTest
{
    public function testStringDefaultSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->string('column');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $this->assertSame(255, $column->getSize());
    }

    public function testStringDefaultSizeViaMagicMethod(): void
    {
        $schema = $this->schema('table');

        $column = $schema->column('column')->__call('string');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $this->assertSame(255, $column->getSize());
    }

    public function testStringSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->string('column', size: 64);
        $schema->save();
        $this->assertSameAsInDB($schema);

        $this->assertSame(64, $column->getSize());
    }
}
