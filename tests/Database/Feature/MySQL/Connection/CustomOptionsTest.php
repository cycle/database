<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Connection;

use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Feature\Common\Connection\CustomOptionsTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class CustomOptionsTest extends CommonClass
{
    #[Test]
    #[DataProvider('dataUnsignedAndZerofillAttributes')]
    public function testUnsignedAndZerofillAttributes(
        string $type,
        string $columnName,
        array $attributes = [],
        array $expectedAttributes = [],
    ): void {
        $schema = $this->schema(\uniqid("{$type}_{$columnName}"));
        \call_user_func_array([$schema, $type], \array_merge([$columnName], $attributes));
        $schema->save();

        Assert::instanceOf($column = $this->fetchSchema($schema)->column($columnName), MySQLColumn::class);
        foreach ($expectedAttributes as $k => $v) {
            Assert::same($column->{(\is_bool($v) ? 'is' : 'get') . \ucwords($k)}(), $v);
            Assert::array($column->getAttributes())->hasKeys($k);
            Assert::same($column->getAttributes()[$k], $v);
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

    #[Test]
    public function testNamedArgumentsToConfigureInteger(): void
    {
        $schema = $this->schema('foo');
        $schema->bigPrimary('id', zerofill: true)->unsigned(true);
        $schema->bigInteger('foo', nullable: true, unsigned: true, zerofill: true, size: 18);
        $schema->save();

        Assert::instanceOf($id = $this->fetchSchema($schema)->column('id'), MySQLColumn::class);
        Assert::instanceOf($foo = $this->fetchSchema($schema)->column('foo'), MySQLColumn::class);

        \assert($id instanceof MySQLColumn);
        \assert($foo instanceof MySQLColumn);
        Assert::true($id->isZerofill());
        Assert::true($foo->isZerofill());
        Assert::true($id->isUnsigned());
        Assert::true($foo->isUnsigned());
        Assert::same($id->getSize(), 20);
        Assert::same($foo->getSize(), 18);
        Assert::false($id->isNullable());
        Assert::true($foo->isNullable());
    }

    #[Test]
    public function testNamedArgumentsToConfigureBoolean(): void
    {
        $schema = $this->schema('foo');
        $schema->boolean('bar')->defaultValue(false)->nullable(false)->unsigned(true)->size(1)->zerofill(true);
        $schema->boolean('baz', nullable: true, unsigned: true, size: 1, zerofill: true);
        $schema->boolean('qux', nullable: false, unsigned: true);
        $schema->boolean('quux', nullable: false);
        $schema->save();

        Assert::instanceOf($bar = $this->fetchSchema($schema)->column('bar'), MySQLColumn::class);
        Assert::instanceOf($baz = $this->fetchSchema($schema)->column('baz'), MySQLColumn::class);
        Assert::instanceOf($qux = $this->fetchSchema($schema)->column('qux'), MySQLColumn::class);
        Assert::instanceOf($quux = $this->fetchSchema($schema)->column('quux'), MySQLColumn::class);

        Assert::instanceOf($bar, MySQLColumn::class);
        Assert::instanceOf($baz, MySQLColumn::class);
        Assert::instanceOf($qux, MySQLColumn::class);
        Assert::instanceOf($quux, MySQLColumn::class);
        Assert::true($bar->isZerofill());
        Assert::true($baz->isZerofill());
        Assert::false($qux->isZerofill());
        Assert::false($quux->isZerofill());
        Assert::true($bar->isUnsigned());
        Assert::true($baz->isUnsigned());
        Assert::true($qux->isUnsigned());
        Assert::false($quux->isUnsigned());

        Assert::same($bar->getSize(), 1);
        Assert::same($baz->getSize(), 1);
        // In case of zerofill=false and unsigned=true the size value might be resolved to 4
        Assert::true(\in_array($qux->getSize(), [1, 4], true));
        Assert::same($quux->getSize(), 1);

        Assert::false($bar->isNullable());
        Assert::true($baz->isNullable());
        Assert::false($qux->isNullable());
        Assert::false($quux->isNullable());
    }

    /**
     * The `text` have no  the `unsigned` attribute. It will be stored in the additional attributes.
     */
    #[Test]
    public function testTextWithUnsigned(): void
    {
        $schema = $this->schema('foo');
        $column = $schema->text('text')->unsigned(true);

        Assert::false($column->isUnsigned());
        Assert::array($column->getAttributes())->hasKeys('unsigned');
    }
}
