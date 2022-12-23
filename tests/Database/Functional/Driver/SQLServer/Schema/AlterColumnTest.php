<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\AlterColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class AlterColumnTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

    public function testDatetimeColumnSizeException(): void
    {
        $this->expectException(SchemaException::class);
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->datetime('datetime', -1);
        $schema->save();
    }

    public function testDatetimeColumnSize2Exception(): void
    {
        $this->expectException(SchemaException::class);
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->datetime('datetime', 8);
        $schema->save();
    }

    public function testChangeDatetimeSize(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame(0, $this->fetchSchema($schema)->column('datetime')->getSize());

        $schema->datetime->datetime(7);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(7, $this->fetchSchema($schema)->column('datetime')->getSize());
    }
}
