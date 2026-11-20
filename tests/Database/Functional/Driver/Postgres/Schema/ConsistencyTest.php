<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ConsistencyTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class ConsistencyTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testPrimary(): void
    {
        /**
         * @var PostgresDriver $d
         */
        $d = $this->database->getDriver();

        $schema = $d->getSchema('table');
        \Testo\Assert::false($schema->exists());

        $schema->string('value');
        $schema->save();

        \Testo\Assert::same($d->getPrimaryKey('', 'table'), null);

        $schema->declareDropped();
        $schema->save();

        $schema = $d->getSchema('table');
        $column = $schema->primary('target');
        $schema->save();

        $schema = $d->getSchema('table');
        \Testo\Assert::true($schema->exists());

        $savedColumn = $schema->getColumns()['target'];
        \Testo\Assert::same($column->getInternalType(), $savedColumn->getInternalType());
        \Testo\Assert::true($this->getPrivatePropertyValue($savedColumn, 'isPrimary'));
        \Testo\Assert::true($this->getPrivatePropertyValue($column, 'isPrimary'));
        \Testo\Assert::instanceOf($savedColumn->getDefaultValue(), FragmentInterface::class);
        \Testo\Assert::same($d->getPrimaryKey('', 'table'), 'target');
    }

    #[\Testo\Test]
    public function testPrimaryException(): void
    {
        /** @var PostgresDriver $d */
        $d = $this->database->getDriver();

        \Testo\Expect::exception(\Cycle\Database\Exception\DriverException::class);

        \Testo\Assert::same($d->getPrimaryKey('', 'table'), 'target');
    }

    #[\Testo\Test]
    public function testBigPrimary(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->bigPrimary('target');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $savedColumn = $schema->getColumns()['target'];
        \Testo\Assert::same($column->getInternalType(), $savedColumn->getInternalType());
        \Testo\Assert::true($this->getPrivatePropertyValue($savedColumn, 'isPrimary'));
        \Testo\Assert::true($this->getPrivatePropertyValue($column, 'isPrimary'));
        \Testo\Assert::instanceOf($savedColumn->getDefaultValue(), FragmentInterface::class);
    }

    #[\Testo\Test]
    public function testSmallPrimary(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->smallPrimary('smallPrimary');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $savedColumn = $schema->getColumns()['smallPrimary'];
        \Testo\Assert::same($column->getInternalType(), $savedColumn->getInternalType());
        \Testo\Assert::true($this->getPrivatePropertyValue($savedColumn, 'isPrimary'));
        \Testo\Assert::true($this->getPrivatePropertyValue($column, 'isPrimary'));
        \Testo\Assert::instanceOf($savedColumn->getDefaultValue(), FragmentInterface::class);
    }
}
