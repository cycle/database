<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\SQLite;

class AlterColumnTest extends \Spiral\Database\Tests\AlterColumnTest
{
    const DRIVER = 'sqlite';

    // SQLite does not support sting length
    public function testChangeSize()
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
