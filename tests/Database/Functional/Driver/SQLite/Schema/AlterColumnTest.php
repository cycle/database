<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
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
}
