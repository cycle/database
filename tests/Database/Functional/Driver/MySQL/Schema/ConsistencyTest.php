<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ConsistencyTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class ConsistencyTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testSet(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        /** @var MySQLColumn $column */
        $column = $schema->set('target', ['a', 'b', 'value']);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::same($column->getEnumValues(), ['a', 'b', 'value']);
        \Testo\Assert::same($schema->column('target')->getEnumValues(), ['a', 'b', 'value']);
    }

    #[\Testo\Test]
    public function testSmallPrimary(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->smallInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::notSame($schema->column('target'), $column);
    }
}
