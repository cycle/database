<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\Postgres;

use Spiral\Database\Driver\Postgres\PostgresDriver;
use Spiral\Database\Injection\FragmentInterface;

class ConsistencyTest extends \Spiral\Database\Tests\ConsistencyTest
{
    public const DRIVER = 'postgres';

    public function testPrimary(): void
    {
        /**
         * @var PostgresDriver $d
         */
        $d = $this->getDriver();

        $schema = $d->getSchema('table');
        $this->assertFalse($schema->exists());

        $schema->string('value');
        $schema->save();

        $this->assertSame(null, $d->getPrimaryKey('', 'table'));

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

        $this->assertSame('target', $d->getPrimaryKey('', 'table'));
    }

    /**
     * @expectedException \Spiral\Database\Exception\DriverException
     */
    public function testPrimaryException(): void
    {
        /**
         * @var PostgresDriver $d
         */
        $d = $this->getDriver();

        $this->assertSame('target', $d->getPrimaryKey('', 'table'));
    }

    public function testBigPrimary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->bigPrimary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame(
            $schema->column('target')->getInternalType(),
            $column->getInternalType()
        );

        $this->assertInstanceOf(
            FragmentInterface::class,
            $schema->column('target')->getDefaultValue()
        );
    }
}
