<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Connection;

// phpcs:ignore
use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Functional\Driver\Common\Connection\CustomOptionsTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class CustomOptionsTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('dataUnsignedAndZerofillAttributes')]
    public function testUnsignedAndZerofillAttributes(
        string $type,
        string $columnName,
        array $attributes = [],
        array $expectedAttributes = [],
    ): void {
        $schema = $this->schema(\uniqid("{$type}_{$columnName}"));
        \call_user_func_array([$schema, $type], \array_merge([$columnName], $attributes));
        $schema->save();

        \Testo\Assert::instanceOf($column = $this->fetchSchema($schema)->column($columnName), MySQLColumn::class);
        foreach ($expectedAttributes as $k => $v) {
            \Testo\Assert::same($column->{(\is_bool($v) ? 'is' : 'get') . \ucwords($k)}(), $v);
            \Testo\Assert::array($column->getAttributes())->hasKeys($k);
            \Testo\Assert::same($column->getAttributes()[$k], $v);
        }
    }

    public function dataUnsignedAndZerofillAttributes(): iterable
    {
        $types = [
            'tinyInteger',
            'smallInteger',
            'integer',
            'bigInteger',
            'primary',
            'bigPrimary',
        ];
        $attr = [
            '_u' => [
                ['unsigned' => true],
                ['unsigned' => true],
            ],
            '_z' => [
                ['zerofill' => true],
                // If you specify ZEROFILL for a numeric column, MySQL automatically adds the UNSIGNED attribute.
                ['unsigned' => true, 'zerofill' => true],
            ],
            '_uz' => [
                ['unsigned' => true, 'zerofill' => true],
                ['unsigned' => true, 'zerofill' => true],
            ],
        ];

        foreach ($types as $t) {
            yield $t => [$t, $t];

            foreach ($attr as $suffix => $options) {
                yield "{$t}{$suffix}" => [$t, "{$t}{$suffix}", $options[0], $options[1]];
            }
        }
    }

    #[\Testo\Test]
    public function testNamedArgumentsToConfigureInteger(): void
    {
        $schema = $this->schema('foo');
        $schema->bigPrimary('id', zerofill: true)->unsigned(true);
        $schema->bigInteger('foo', nullable: true, unsigned: true, zerofill: true, size: 18);
        $schema->save();

        \Testo\Assert::instanceOf($id = $this->fetchSchema($schema)->column('id'), MySQLColumn::class);
        \Testo\Assert::instanceOf($foo = $this->fetchSchema($schema)->column('foo'), MySQLColumn::class);

        \assert($id instanceof MySQLColumn);
        \assert($foo instanceof MySQLColumn);
        \Testo\Assert::true($id->isZerofill());
        \Testo\Assert::true($foo->isZerofill());
        \Testo\Assert::true($id->isUnsigned());
        \Testo\Assert::true($foo->isUnsigned());
        \Testo\Assert::same($id->getSize(), 20);
        \Testo\Assert::same($foo->getSize(), 18);
        \Testo\Assert::false($id->isNullable());
        \Testo\Assert::true($foo->isNullable());
    }

    #[\Testo\Test]
    public function testNamedArgumentsToConfigureBoolean(): void
    {
        $schema = $this->schema('foo');
        $schema->boolean('bar')->defaultValue(false)->nullable(false)->unsigned(true)->size(1)->zerofill(true);
        $schema->boolean('baz', nullable: true, unsigned: true, size: 1, zerofill: true);
        $schema->boolean('qux', nullable: false, unsigned: true);
        $schema->boolean('quux', nullable: false);
        $schema->save();

        \Testo\Assert::instanceOf($bar = $this->fetchSchema($schema)->column('bar'), MySQLColumn::class);
        \Testo\Assert::instanceOf($baz = $this->fetchSchema($schema)->column('baz'), MySQLColumn::class);
        \Testo\Assert::instanceOf($qux = $this->fetchSchema($schema)->column('qux'), MySQLColumn::class);
        \Testo\Assert::instanceOf($quux = $this->fetchSchema($schema)->column('quux'), MySQLColumn::class);

        \Testo\Assert::instanceOf($bar, MySQLColumn::class);
        \Testo\Assert::instanceOf($baz, MySQLColumn::class);
        \Testo\Assert::instanceOf($qux, MySQLColumn::class);
        \Testo\Assert::instanceOf($quux, MySQLColumn::class);
        \Testo\Assert::true($bar->isZerofill());
        \Testo\Assert::true($baz->isZerofill());
        \Testo\Assert::false($qux->isZerofill());
        \Testo\Assert::false($quux->isZerofill());
        \Testo\Assert::true($bar->isUnsigned());
        \Testo\Assert::true($baz->isUnsigned());
        \Testo\Assert::true($qux->isUnsigned());
        \Testo\Assert::false($quux->isUnsigned());

        \Testo\Assert::same($bar->getSize(), 1);
        \Testo\Assert::same($baz->getSize(), 1);
        // In case of zerofill=false and unsigned=true the size value might be resolved to 4
        \Testo\Assert::true(\in_array($qux->getSize(), [1, 4], true));
        \Testo\Assert::same($quux->getSize(), 1);

        \Testo\Assert::false($bar->isNullable());
        \Testo\Assert::true($baz->isNullable());
        \Testo\Assert::false($qux->isNullable());
        \Testo\Assert::false($quux->isNullable());
    }

    /**
     * The `text` have no  the `unsigned` attribute. It will be stored in the additional attributes.
     */
    #[\Testo\Test]
    public function testTextWithUnsigned(): void
    {
        $schema = $this->schema('foo');
        $column = $schema->text('text')->unsigned(true);

        \Testo\Assert::false($column->isUnsigned());
        \Testo\Assert::array($column->getAttributes())->hasKeys('unsigned');
    }
}
