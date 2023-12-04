<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class BinaryColumnTest extends BaseTest
{
    public const DRIVER = 'sqlserver';

    public function testBinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('varbinary', $schema->column('binary_data')->getInternalType());
        $this->assertSame(0, $schema->column('binary_data')->getSize());
    }

    public function testBinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data', size: 16);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('varbinary', $schema->column('binary_data')->getInternalType());
        $this->assertSame(16, $schema->column('binary_data')->getSize());
    }

    public function testVarbinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('varbinary', $schema->column('binary_data')->getInternalType());
        $this->assertSame(0, $schema->column('binary_data')->getSize());
    }

    public function testVarbinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data', size: 16);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('varbinary', $schema->column('binary_data')->getInternalType());
        $this->assertSame(16, $schema->column('binary_data')->getSize());
    }
}
