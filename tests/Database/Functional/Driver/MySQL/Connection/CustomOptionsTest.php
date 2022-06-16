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
    public function testUnsignedAndZerofillAttributes(string $type, string $columnName, array $attribute = [], array $expectedAttributes = []): void
    {
        $schema = $this->schema(uniqid("{$type}_{$columnName}"));
        $schema->primary('id');
        $schema->{$type}($columnName)->setAttributes($attribute);
        $schema->save();

        self::assertInstanceOf(MySQLColumn::class, $column = $this->fetchSchema($schema)->column($columnName));
        self::assertEquals($expectedAttributes, $column->getAttributes());
    }

    public function dataUnsignedAndZerofillAttributes(): iterable
    {
        $types = [
            'tinyInteger',
            'smallInteger',
            'integer',
            'bigInteger'
        ];
        $attr = [
            '_u' => [
                ['unsigned' => true],
                ['unsigned' => true],
            ],
            '_z' => [
                ['zerofill' => true],
                // If you specify ZEROFILL for a numeric column, MySQL automatically adds the UNSIGNED attribute.
                ['unsigned' => true, 'zerofill' => true]
            ],
            '_uz' => [
                ['unsigned' => true, 'zerofill' => true],
                ['unsigned' => true, 'zerofill' => true]
            ],
        ];

        foreach ($types as $t) {
            yield $t => [$t, $t];

            foreach ($attr as $suffix => $options) {
                yield "{$t}{$suffix}" => [$t, "{$t}{$suffix}", $options[0], $options[1]];
            }
        }
    }
}
