<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[ExceptDriver(Driver::Postgres)]
class StringColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testStringDefaultSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->string('column');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        Assert::same($column->getSize(), 255);
    }

    #[Test]
    public function testStringDefaultSizeViaMagicMethod(): void
    {
        $schema = $this->schema('table');

        $column = $schema->column('column')->__call('string');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        Assert::same($column->getSize(), 255);
    }

    #[Test]
    public function testStringSize(): void
    {
        $schema = $this->schema('table');

        $column = $schema->string('column', size: 64);
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        Assert::same($column->getSize(), 64);
    }
}
