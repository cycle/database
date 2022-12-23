<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\AlterColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class AlterColumnTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    // SQLite does not support sting length
    public function testChangeSize(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame(255, $this->fetchSchema($schema)->column('first_name')->getSize());

        $schema->string('first_name', 100);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(255, $this->fetchSchema($schema)->column('first_name')->getSize());
    }

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

        $schema->datetime('datetime', 7);
        $schema->save();
    }

    public function testChangeDatetimeSize(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame(0, $this->fetchSchema($schema)->column('datetime')->getSize());

        $schema->datetime->string(6);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(6, $this->fetchSchema($schema)->column('datetime')->getSize());
    }
}
