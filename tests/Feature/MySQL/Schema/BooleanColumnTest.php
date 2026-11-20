<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\BooleanColumnTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class BooleanColumnTest extends CommonClass
{
    #[Test]
    public function testBooleanDefaultSize(): void
    {
        $schema = $this->schema('table');
        $schema->boolean('column');
        $schema->save();

        $column = $this->fetchSchema($schema)->column('column');

        Assert::same($column->getAbstractType(), 'boolean');
        Assert::same($column->getSize(), 1);
    }

    #[Test]
    public function testBooleanComparisonWithSize(): void
    {
        $schema = $this->schema('table');
        $foo = $schema->boolean('foo')->defaultValue(false)->nullable(false)->unsigned(true)->size(1)->zerofill(true);
        $bar = $schema->boolean('bar', nullable: true, unsigned: true, size: 1, zerofill: true);
        $baz = $schema->boolean('baz', nullable: false, unsigned: true);
        $mux = $schema->boolean('mux', nullable: false);
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::same($foo->getSize(), 1);
        Assert::same($bar->getSize(), 1);
        Assert::same($baz->getSize(), 1);
        Assert::same($mux->getSize(), 1);
        Assert::same($schema->column('foo')->getSize(), 1);
        Assert::same($schema->column('bar')->getSize(), 1);
        Assert::true(\in_array($schema->column('baz')->getSize(), [1, 4], true));
        Assert::same($schema->column('mux')->getSize(), 1);
        Assert::true($foo->compare($schema->column('foo')));
        Assert::true($bar->compare($schema->column('bar')));
        Assert::true($baz->compare($schema->column('baz')));
        Assert::true($mux->compare($schema->column('mux')));
    }

    #[Test]
    public function testBooleanWithProblematicValues(): void
    {
        $schema = $this->schema('table');

        $column = $schema->boolean('target')
            ->defaultValue(false)
            ->nullable(false)
            ->unsigned(true)
            ->comment('Target comment');

        $schema->save();

        Assert::true($schema->exists());

        $schema = $this->schema('table');
        $target = $schema->column('target');

        Assert::same($column->getSize(), 1);
        Assert::same($target->getSize(), 4);
        Assert::false($column->isNullable());
        Assert::false($target->isNullable());
        Assert::true($column->isUnsigned());
        Assert::true($target->isUnsigned());

        $object = new \ReflectionObject($target);
        $property = $object->getProperty('defaultValue');
        $property->setAccessible(true);
        $defaultValue = $property->getValue($target);

        Assert::same($column->getDefaultValue(), false);
        Assert::same($target->getDefaultValue(), 0);
        Assert::same($defaultValue, '0');
        Assert::true($column->compare($target));
        Assert::true($target->compare($column));
        // The size was not changed
        Assert::same($target->getSize(), 4);
    }
}
