<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\NumberColumnTest as BaseTest;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
final class NumberColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->integer('integer_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('integer_data')->getInternalType(), 'int');
        \Testo\Assert::same($schema->column('integer_data')->getSize(), 11);
    }

    #[\Testo\Test]
    public function testIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->integer('integer_data', size: 7);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('integer_data')->getInternalType(), 'int');
        \Testo\Assert::same($schema->column('integer_data')->getSize(), 7);
    }

    #[\Testo\Test]
    public function testTinyInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->tinyInteger('tiny_integer_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('tiny_integer_data')->getInternalType(), 'tinyint');
        \Testo\Assert::same($schema->column('tiny_integer_data')->getSize(), 4);
    }

    #[\Testo\Test]
    public function testTinyIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->tinyInteger('tiny_integer_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('tiny_integer_data')->getInternalType(), 'tinyint');
        \Testo\Assert::same($schema->column('tiny_integer_data')->getSize(), 3);
    }

    #[\Testo\Test]
    public function testSmallInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->smallInteger('small_integer_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('small_integer_data')->getInternalType(), 'smallint');
        \Testo\Assert::same($schema->column('small_integer_data')->getSize(), 6);
    }

    #[\Testo\Test]
    public function testSmallIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->smallInteger('small_integer_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('small_integer_data')->getInternalType(), 'smallint');
        \Testo\Assert::same($schema->column('small_integer_data')->getSize(), 3);
    }

    #[\Testo\Test]
    public function testBigInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->bigInteger('big_integer_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('big_integer_data')->getInternalType(), 'bigint');
        \Testo\Assert::same($schema->column('big_integer_data')->getSize(), 20);
    }

    #[\Testo\Test]
    public function testBigIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->bigInteger('big_integer_data', size: 17);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('big_integer_data')->getInternalType(), 'bigint');
        \Testo\Assert::same($schema->column('big_integer_data')->getSize(), 17);
    }
}
