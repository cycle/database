<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
final class BinaryColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testBinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('binary_data')->getInternalType(), 'blob');
        \Testo\Assert::same($schema->column('binary_data')->getSize(), 0);
    }

    #[\Testo\Test]
    public function testBinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data', 16);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        \Testo\Assert::same($schema->column('binary_data')->getSize(), 16);
    }

    #[\Testo\Test]
    public function testVarbinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        \Testo\Assert::same($schema->column('binary_data')->getSize(), 255);
    }

    #[\Testo\Test]
    public function testVarbinaryDefaultSizeViaMagicMethod(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->column('binary_data')->__call('varbinary');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        \Testo\Assert::same($schema->column('binary_data')->getSize(), 255);
    }

    #[\Testo\Test]
    public function testVarbinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data', 16);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        \Testo\Assert::same($schema->column('binary_data')->getSize(), 16);
    }
}
