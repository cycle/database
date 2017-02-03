<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\Postgres;

use Spiral\Database\Injections\FragmentInterface;

class SchemaConsistencyTest extends \Spiral\Tests\Database\SchemaConsistencyTest
{
    use DriverTrait;

    public function testPrimary()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->primary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame($schema->column('target')->getType(), $column->getType());

        $this->assertInstanceOf(
            FragmentInterface::class,
            $schema->column('target')->getDefaultValue()
        );
    }

    public function testBigPrimary()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->bigPrimary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame($schema->column('target')->getType(), $column->getType());

        $this->assertInstanceOf(
            FragmentInterface::class,
            $schema->column('target')->getDefaultValue()
        );
    }
}