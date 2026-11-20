<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Feature\Common\Schema\NumberColumnTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class NumberColumnTest extends CommonClass
{
    #[Test]
    public function testInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->integer('integer_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('integer_data')->getInternalType(), 'int');
        Assert::same($schema->column('integer_data')->getSize(), 11);
    }

    #[Test]
    public function testIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->integer('integer_data', size: 7);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('integer_data')->getInternalType(), 'int');
        Assert::same($schema->column('integer_data')->getSize(), 7);
    }

    #[Test]
    public function testTinyInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->tinyInteger('tiny_integer_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('tiny_integer_data')->getInternalType(), 'tinyint');
        Assert::same($schema->column('tiny_integer_data')->getSize(), 4);
    }

    #[Test]
    public function testTinyIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->tinyInteger('tiny_integer_data', size: 3);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('tiny_integer_data')->getInternalType(), 'tinyint');
        Assert::same($schema->column('tiny_integer_data')->getSize(), 3);
    }

    #[Test]
    public function testSmallInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->smallInteger('small_integer_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('small_integer_data')->getInternalType(), 'smallint');
        Assert::same($schema->column('small_integer_data')->getSize(), 6);
    }

    #[Test]
    public function testSmallIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->smallInteger('small_integer_data', size: 3);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('small_integer_data')->getInternalType(), 'smallint');
        Assert::same($schema->column('small_integer_data')->getSize(), 3);
    }

    #[Test]
    public function testBigInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->bigInteger('big_integer_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('big_integer_data')->getInternalType(), 'bigint');
        Assert::same($schema->column('big_integer_data')->getSize(), 20);
    }

    #[Test]
    public function testBigIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->bigInteger('big_integer_data', size: 17);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('big_integer_data')->getInternalType(), 'bigint');
        Assert::same($schema->column('big_integer_data')->getSize(), 17);
    }
}
