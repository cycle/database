<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\NumberColumnTest as BaseTest;

/**
 * @group driver
 * @group driver-mysql
 */
final class NumberColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';

    public function testInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->integer('integer_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('int', $schema->column('integer_data')->getInternalType());
        $this->assertSame(11, $schema->column('integer_data')->getSize());
    }

    public function testIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->integer('integer_data', size: 7);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('int', $schema->column('integer_data')->getInternalType());
        $this->assertSame(7, $schema->column('integer_data')->getSize());
    }

    public function testTinyInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->tinyInteger('tiny_integer_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('tinyint', $schema->column('tiny_integer_data')->getInternalType());
        $this->assertSame(4, $schema->column('tiny_integer_data')->getSize());
    }

    public function testTinyIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->tinyInteger('tiny_integer_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('tinyint', $schema->column('tiny_integer_data')->getInternalType());
        $this->assertSame(3, $schema->column('tiny_integer_data')->getSize());
    }

    public function testSmallInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->smallInteger('small_integer_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('smallint', $schema->column('small_integer_data')->getInternalType());
        $this->assertSame(6, $schema->column('small_integer_data')->getSize());
    }

    public function testSmallIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->smallInteger('small_integer_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('smallint', $schema->column('small_integer_data')->getInternalType());
        $this->assertSame(3, $schema->column('small_integer_data')->getSize());
    }

    public function testBigInteger(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->bigInteger('big_integer_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('bigint', $schema->column('big_integer_data')->getInternalType());
        $this->assertSame(20, $schema->column('big_integer_data')->getSize());
    }

    public function testBigIntegerWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->bigInteger('big_integer_data', size: 17);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('bigint', $schema->column('big_integer_data')->getInternalType());
        $this->assertSame(17, $schema->column('big_integer_data')->getSize());
    }
}
