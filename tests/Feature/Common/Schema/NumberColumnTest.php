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
class NumberColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testSetPrecisionAndScaleViaAttribute(): void
    {
        $schema = $this->schema('table');

        $column = $schema->column('column')->__call('decimal', ['precision' => 8, 'scale' => 2]);
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        Assert::same($column->getPrecision(), 8);
        Assert::same($column->getScale(), 2);
    }
}
