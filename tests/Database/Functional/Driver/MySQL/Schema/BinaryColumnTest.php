<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-mysql
 */
final class BinaryColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';

    public function testBinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('blob', $schema->column('binary_data')->getInternalType());
        $this->assertSame(0, $schema->column('binary_data')->getSize());
    }

    public function testBinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data', 16);
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
        $this->assertSame(255, $schema->column('binary_data')->getSize());
    }

    public function testVarbinaryDefaultSizeViaMagicMethod(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->column('binary_data')->__call('varbinary');
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('varbinary', $schema->column('binary_data')->getInternalType());
        $this->assertSame(255, $schema->column('binary_data')->getSize());
    }

    public function testVarbinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data', 16);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('varbinary', $schema->column('binary_data')->getInternalType());
        $this->assertSame(16, $schema->column('binary_data')->getSize());
    }
}
