<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Connection;

// phpcs:ignore
use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Functional\Driver\Common\Connection\CustomOptionsTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class CustomOptionsTest extends CommonClass
{
    public const DRIVER = 'mysql';

    /**
     * @dataProvider dataUnsignedAndZerofillAttributes
     */
    public function testUnsignedAndZerofillAttributes(
        string $type,
        string $columnName,
        array $attributes = [],
        array $expectedAttributes = [],
    ): void {
        $schema = $this->schema(\uniqid("{$type}_{$columnName}"));
        \call_user_func_array([$schema, $type], \array_merge([$columnName], $attributes));
        $schema->save();

        $this->assertInstanceOf(MySQLColumn::class, $column = $this->fetchSchema($schema)->column($columnName));
        foreach ($expectedAttributes as $k => $v) {
            $this->assertSame($v, $column->{(\is_bool($v) ? 'is' : 'get') . \ucwords($k)}());
            $this->assertArrayHasKey($k, $column->getAttributes());
            $this->assertSame($v, $column->getAttributes()[$k]);
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

    public function testNamedArgumentsToConfigureInteger(): void
    {
        $schema = $this->schema('foo');
        $schema->bigPrimary('id', zerofill: true)->unsigned(true);
        $schema->bigInteger('foo', nullable: true, unsigned: true, zerofill: true, size: 18);
        $schema->save();

        $this->assertInstanceOf(MySQLColumn::class, $id = $this->fetchSchema($schema)->column('id'));
        $this->assertInstanceOf(MySQLColumn::class, $foo = $this->fetchSchema($schema)->column('foo'));

        \assert($id instanceof MySQLColumn);
        \assert($foo instanceof MySQLColumn);
        $this->assertTrue($id->isZerofill());
        $this->assertTrue($foo->isZerofill());
        $this->assertTrue($id->isUnsigned());
        $this->assertTrue($foo->isUnsigned());
        $this->assertSame(20, $id->getSize());
        $this->assertSame(18, $foo->getSize());
        $this->assertFalse($id->isNullable());
        $this->assertTrue($foo->isNullable());
    }

    public function testNamedArgumentsToConfigureBoolean(): void
    {
        $schema = $this->schema('foo');
        $schema->boolean('bar')->defaultValue(false)->unsigned(true)->size(1);
        $schema->boolean('baz', nullable: true, unsigned: true, size: 1);
        $schema->save();

        $this->assertInstanceOf(MySQLColumn::class, $bar = $this->fetchSchema($schema)->column('bar'));
        $this->assertInstanceOf(MySQLColumn::class, $baz = $this->fetchSchema($schema)->column('baz'));

        \assert($bar instanceof MySQLColumn);
        \assert($baz instanceof MySQLColumn);
        $this->assertFalse($bar->isZerofill());
        $this->assertFalse($baz->isZerofill());
        $this->assertTrue($bar->isUnsigned());
        $this->assertTrue($baz->isUnsigned());

        // Below assertion fails. We expect boolean to have a default size of 1 however we receive 4,
        // even when explicitly declaring the column size. Likely due to MySQLColumn::createInstance
        // not knowing the `userType` at this point, or that the default size should be 1. Instead, the
        // size is resolved as 4, default value when resolving a tinyint (DBMS specific column type).
        $this->assertSame(1, $bar->getSize());
        $this->assertSame(1, $baz->getSize());
        $this->assertFalse($bar->isNullable());
        $this->assertTrue($baz->isNullable());
    }

    /**
     * The `text` have no  the `unsigned` attribute. It will be stored in the additional attributes.
     */
    public function testTextWithUnsigned(): void
    {
        $schema = $this->schema('foo');
        $column = $schema->text('text')->unsigned(true);

        $this->assertFalse($column->isUnsigned());
        $this->assertArrayHasKey('unsigned', $column->getAttributes());
    }
}
