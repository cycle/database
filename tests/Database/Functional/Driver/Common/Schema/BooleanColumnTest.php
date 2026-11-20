<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class BooleanColumnTest extends BaseTest
{
    #[\Testo\Test]
    public function testBooleanSchema(): void
    {
        $schema = $this->schema('table');

        $schema->boolean('column');
        $schema->save();
        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testBooleanAbstractType(): void
    {
        $schema = $this->schema('table');

        $schema->boolean('column');
        $schema->save();

        $column = $this->fetchSchema($schema)->column('column');

        \Testo\Assert::same($column->getAbstractType(), 'boolean');
    }
}
