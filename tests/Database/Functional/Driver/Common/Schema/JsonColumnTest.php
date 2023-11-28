<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class JsonColumnTest extends BaseTest
{
    public function testColumnSizeIsIgnored(): void
    {
        $schema = $this->schema('table');
        $schema->primary('id');
        $schema->json('json_data', size: 255);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $this->assertSame(0, $schema->column('json_data')->getSize());
    }
}
