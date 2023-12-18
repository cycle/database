<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class NumberColumnTest extends BaseTest
{
    public function testSetPrecisionAndScaleViaAttribute(): void
    {
        $schema = $this->schema('table');

        $column = $schema->column('column')->__call('decimal', ['precision' => 8, 'scale' => 2]);
        $schema->save();
        $this->assertSameAsInDB($schema);

        $this->assertSame(8, $column->getPrecision());
        $this->assertSame(2, $column->getScale());
    }
}
