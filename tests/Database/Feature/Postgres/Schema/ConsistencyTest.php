<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Tests\Feature\Common\Schema\ConsistencyTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class ConsistencyTest extends CommonClass
{
    #[Test]
    public function testPrimary(): void
    {
        /**
         * @var PostgresDriver $d
         */
        $d = $this->database->getDriver();

        $schema = $d->getSchema('table');
        Assert::false($schema->exists());

        $schema->string('value');
        $schema->save();

        Assert::same($d->getPrimaryKey('', 'table'), null);

        $schema->declareDropped();
        $schema->save();

        $schema = $d->getSchema('table');
        $column = $schema->primary('target');
        $schema->save();

        $schema = $d->getSchema('table');
        Assert::true($schema->exists());

        $savedColumn = $schema->getColumns()['target'];
        Assert::same($column->getInternalType(), $savedColumn->getInternalType());
        Assert::true((new \ReflectionProperty($savedColumn, 'isPrimary'))->getValue($savedColumn));
        Assert::true((new \ReflectionProperty($column, 'isPrimary'))->getValue($column));
        Assert::instanceOf($savedColumn->getDefaultValue(), FragmentInterface::class);
        Assert::same($d->getPrimaryKey('', 'table'), 'target');
    }

    #[Test]
    public function testPrimaryException(): void
    {
        /** @var PostgresDriver $d */
        $d = $this->database->getDriver();

        Expect::exception(\Cycle\Database\Exception\DriverException::class);

        Assert::same($d->getPrimaryKey('', 'table'), 'target');
    }

    #[Test]
    public function testBigPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->bigPrimary('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $savedColumn = $schema->getColumns()['target'];
        Assert::same($column->getInternalType(), $savedColumn->getInternalType());
        Assert::true((new \ReflectionProperty($savedColumn, 'isPrimary'))->getValue($savedColumn));
        Assert::true((new \ReflectionProperty($column, 'isPrimary'))->getValue($column));
        Assert::instanceOf($savedColumn->getDefaultValue(), FragmentInterface::class);
    }

    #[Test]
    public function testSmallPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->smallPrimary('smallPrimary');
        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $savedColumn = $schema->getColumns()['smallPrimary'];
        Assert::same($column->getInternalType(), $savedColumn->getInternalType());
        Assert::true((new \ReflectionProperty($savedColumn, 'isPrimary'))->getValue($savedColumn));
        Assert::true((new \ReflectionProperty($column, 'isPrimary'))->getValue($column));
        Assert::instanceOf($savedColumn->getDefaultValue(), FragmentInterface::class);
    }
}
