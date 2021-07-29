<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\SQLite;

/**
 * @group driver
 * @group driver-sqlite
 */
class AlterColumnTest extends \Cycle\Database\Tests\AlterColumnTest
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
