<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class BinaryColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testBinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'blob');
        Assert::same($schema->column('binary_data')->getSize(), 0);
    }

    #[Test]
    public function testBinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data', 16);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        Assert::same($schema->column('binary_data')->getSize(), 16);
    }

    #[Test]
    public function testVarbinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        Assert::same($schema->column('binary_data')->getSize(), 255);
    }

    #[Test]
    public function testVarbinaryDefaultSizeViaMagicMethod(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->column('binary_data')->__call('varbinary');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        Assert::same($schema->column('binary_data')->getSize(), 255);
    }

    #[Test]
    public function testVarbinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data', 16);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        Assert::same($schema->column('binary_data')->getSize(), 16);
    }
}
