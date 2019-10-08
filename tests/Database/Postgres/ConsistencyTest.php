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

        $schema = $d->getSchema('table');
        $this->assertFalse($schema->exists());

        $schema->string('value');
        $schema->save();

        $this->assertSame(null, $d->getPrimary('', 'table'));

        $schema->declareDropped();
        $schema->save();

        $schema = $d->getSchema('table');
        $column = $schema->primary('target');
        $schema->save();

        $schema = $d->getSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame($schema->column('target')->getInternalType(), $column->getInternalType());

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

        $this->assertSame($schema->column('target')->getInternalType(), $column->getInternalType());

        $this->assertInstanceOf(
            FragmentInterface::class,
            $schema->column('target')->getDefaultValue()
        );
    }

    public function testJsonB()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->jsonb('column');
        $schema->save();

        $schema = $this->schema('table');

        $this->assertTrue($schema->exists());
        $this->assertSame('jsonb', $schema->column('column')->getAbstractType());

        $this->assertTrue($schema->column('column')->compare($column));
    }
}
