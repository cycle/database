<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ConsistencyTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ConsistencyTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testSet(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        /** @var MySQLColumn $column */
        $column = $schema->set('target', ['a', 'b', 'value']);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertSame(['a', 'b', 'value'], $column->getEnumValues());
        $this->assertSame(['a', 'b', 'value'], $schema->column('target')->getEnumValues());
    }

    public function testSmallPrimary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->smallInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }
}
