<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
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
        $this->assertFalse($schema->exists());

        /** @var MySQLColumn $column */
        $column = $schema->boolean('column')->nullable(false)->unsigned(true);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertSame(1, $column->getSize());
        $this->assertSame(4, $schema->column('column')->getSize());
        $this->assertTrue($schema->column('column')->compare($column));
        $this->assertTrue($column->compare($schema->column('column')));
    }
}
