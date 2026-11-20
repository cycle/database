<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[ExceptDriver(Driver::MySQL)]
class BooleanColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testBooleanSchema(): void
    {
        $schema = $this->schema('table');

        $schema->boolean('column');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testBooleanAbstractType(): void
    {
        $schema = $this->schema('table');

        $schema->boolean('column');
        $schema->save();

        $column = $this->fetchSchema($schema)->column('column');

        Assert::same($column->getAbstractType(), 'boolean');
    }
}
