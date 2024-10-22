<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class BooleanColumnTest extends BaseTest
{
    public function testBooleanSchema(): void
    {
        $schema = $this->schema('table');

        $schema->boolean('column');
        $schema->save();
        $this->assertSameAsInDB($schema);
    }

    public function testBooleanAbstractType(): void
    {
        $schema = $this->schema('table');

        $schema->boolean('column');
        $schema->save();

        $column = $this->fetchSchema($schema)->column('column');

        $this->assertSame('boolean', $column->getAbstractType());
    }
}
