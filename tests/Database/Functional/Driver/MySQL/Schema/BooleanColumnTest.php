<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\BooleanColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class BooleanColumnTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testBooleanDefaultSize(): void
    {
        $schema = $this->schema('table');
        $schema->boolean('column');
        $schema->save();

        $column = $this->fetchSchema($schema)->column('column');

        \Testo\Assert::same($column->getAbstractType(), 'boolean');
        \Testo\Assert::same($column->getSize(), 1);
    }

    #[\Testo\Test]
    public function testBooleanComparisonWithSize(): void
    {
        $schema = $this->schema('table');
        $foo = $schema->boolean('foo')->defaultValue(false)->nullable(false)->unsigned(true)->size(1)->zerofill(true);
        $bar = $schema->boolean('bar', nullable: true, unsigned: true, size: 1, zerofill: true);
        $baz = $schema->boolean('baz', nullable: false, unsigned: true);
        $mux = $schema->boolean('mux', nullable: false);
        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::same($foo->getSize(), 1);
        \Testo\Assert::same($bar->getSize(), 1);
        \Testo\Assert::same($baz->getSize(), 1);
        \Testo\Assert::same($mux->getSize(), 1);
        \Testo\Assert::same($schema->column('foo')->getSize(), 1);
        \Testo\Assert::same($schema->column('bar')->getSize(), 1);
        \Testo\Assert::true(\in_array($schema->column('baz')->getSize(), [1, 4], true));
        \Testo\Assert::same($schema->column('mux')->getSize(), 1);
        \Testo\Assert::true($foo->compare($schema->column('foo')));
        \Testo\Assert::true($bar->compare($schema->column('bar')));
        \Testo\Assert::true($baz->compare($schema->column('baz')));
        \Testo\Assert::true($mux->compare($schema->column('mux')));
    }

    #[\Testo\Test]
    public function testBooleanWithProblematicValues(): void
    {
        $schema = $this->schema('table');

        $column = $schema->boolean('target')
            ->defaultValue(false)
            ->nullable(false)
            ->unsigned(true)
            ->comment('Target comment');

        $schema->save();

        \Testo\Assert::true($schema->exists());

        $schema = $this->schema('table');
        $target = $schema->column('target');

        \Testo\Assert::same($column->getSize(), 1);
        \Testo\Assert::same($target->getSize(), 4);
        \Testo\Assert::false($column->isNullable());
        \Testo\Assert::false($target->isNullable());
        \Testo\Assert::true($column->isUnsigned());
        \Testo\Assert::true($target->isUnsigned());

        $object = new \ReflectionObject($target);
        $property = $object->getProperty('defaultValue');
        $property->setAccessible(true);
        $defaultValue = $property->getValue($target);

        \Testo\Assert::same($column->getDefaultValue(), false);
        \Testo\Assert::same($target->getDefaultValue(), 0);
        \Testo\Assert::same($defaultValue, '0');
        \Testo\Assert::true($column->compare($target));
        \Testo\Assert::true($target->compare($column));
        // The size was not changed
        \Testo\Assert::same($target->getSize(), 4);
    }
}
