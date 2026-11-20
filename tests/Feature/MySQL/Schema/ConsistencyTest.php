<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Feature\Common\Schema\ConsistencyTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class ConsistencyTest extends CommonClass
{
    #[Test]
    public function testSet(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        /** @var MySQLColumn $column */
        $column = $schema->set('target', ['a', 'b', 'value']);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::same($column->getEnumValues(), ['a', 'b', 'value']);
        Assert::same($schema->column('target')->getEnumValues(), ['a', 'b', 'value']);
    }

    #[Test]
    public function testSmallPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->smallInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::notSame($schema->column('target'), $column);
    }
}
