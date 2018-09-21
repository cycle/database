<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\Postgres;

use Spiral\Database\Driver\Postgres\PostgresDriver;
use Spiral\Database\Injection\FragmentInterface;

class ConsistencyTest extends \Spiral\Database\Tests\ConsistencyTest
{
    const DRIVER = 'postgres';

    public function testPrimary()
    {
        /**
         * @var PostgresDriver $d
         */
        $d = $this->getDriver();

        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->string('value');
        $schema->save();

        $this->assertSame(null, $d->getPrimary('', 'table'));

        $schema->declareDropped();
        $schema->save();

        $schema = $this->schema('table');
        $column = $schema->primary('target');
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame($schema->column('target')->getType(), $column->getType());

        $this->assertInstanceOf(
            FragmentInterface::class,
            $schema->column('target')->getDefaultValue()
        );


        $this->assertSame('target', $d->getPrimary('', 'table'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\DriverException
     */
    public function testPrimaryException()
    {
        /**
         * @var PostgresDriver $d
         */
        $d = $this->getDriver();

        $this->assertSame('target', $d->getPrimary('', 'table'));
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