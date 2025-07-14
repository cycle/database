<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\BooleanColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class BooleanColumnTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testBooleanDefaultSize(): void
    {
        $schema = $this->schema('table');
        $schema->boolean('column');
        $schema->save();

        $column = $this->fetchSchema($schema)->column('column');

        $this->assertSame('boolean', $column->getAbstractType());
        $this->assertSame(1, $column->getSize());
    }

    public function testBooleanComparisonWithSize(): void
    {
        $schema = $this->schema('table');
        $foo = $schema->boolean('foo')->defaultValue(false)->nullable(false)->unsigned(true)->size(1)->zerofill(true);
        $bar = $schema->boolean('bar', nullable: true, unsigned: true, size: 1, zerofill: true);
        $baz = $schema->boolean('baz', nullable: false, unsigned: true);
        $mux = $schema->boolean('mux', nullable: false);
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertSame(1, $foo->getSize());
        $this->assertSame(1, $bar->getSize());
        $this->assertSame(1, $baz->getSize());
        $this->assertSame(1, $mux->getSize());
        $this->assertSame(1, $schema->column('foo')->getSize());
        $this->assertSame(1, $schema->column('bar')->getSize());
        $this->assertTrue(\in_array($schema->column('baz')->getSize(), [1, 4], true));
        $this->assertSame(1, $schema->column('mux')->getSize());
        $this->assertTrue($foo->compare($schema->column('foo')));
        $this->assertTrue($bar->compare($schema->column('bar')));
        $this->assertTrue($baz->compare($schema->column('baz')));
        $this->assertTrue($mux->compare($schema->column('mux')));
    }

    public function testBooleanWithProblematicValues(): void
    {
        $schema = $this->schema('table');

        $column = $schema->boolean('target')
            ->defaultValue(false)
            ->nullable(false)
            ->unsigned(true)
            ->comment('Target comment');

        $schema->save();

        $this->assertTrue($schema->exists());

        $schema = $this->schema('table');
        $target = $schema->column('target');

        $this->assertSame(1, $column->getSize());
        $this->assertSame(4, $target->getSize());
        $this->assertFalse($column->isNullable());
        $this->assertFalse($target->isNullable());
        $this->assertTrue($column->isUnsigned());
        $this->assertTrue($target->isUnsigned());

        $object = new \ReflectionObject($target);
        $property = $object->getProperty('defaultValue');
        $property->setAccessible(true);
        $defaultValue = $property->getValue($target);

        $this->assertSame(false, $column->getDefaultValue());
        $this->assertSame(0, $target->getDefaultValue());
        $this->assertSame('0', $defaultValue);
        $this->assertTrue($column->compare($target));
        $this->assertTrue($target->compare($column));
    }
}
