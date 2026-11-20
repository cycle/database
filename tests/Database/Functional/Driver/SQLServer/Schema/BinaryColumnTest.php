<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class BinaryColumnTest extends BaseTest
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
    public function testBinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        \Testo\Assert::same($schema->column('binary_data')->getSize(), 0);
    }

    #[\Testo\Test]
    public function testBinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data', size: 16);
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
        \Testo\Assert::same($schema->column('binary_data')->getSize(), 0);
    }

    #[\Testo\Test]
    public function testVarbinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data', size: 16);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        \Testo\Assert::same($schema->column('binary_data')->getSize(), 16);
    }
}
