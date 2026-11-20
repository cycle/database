<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
final class SerialColumnTest extends BaseTest
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testSmallSerial(): void
    {
        $schema = $this->schema('small_serial');

        /** @var PostgresColumn $column */
        $column = $schema->smallSerial('foo');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('small_serial')->getColumns()['foo'];
        \Testo\Assert::false($this->getPrivatePropertyValue($savedColumn, 'isPrimary'));
        \Testo\Assert::false($this->getPrivatePropertyValue($column, 'isPrimary'));
        \Testo\Assert::false($savedColumn->isNullable());
        \Testo\Assert::same($column->getAbstractType(), 'smallserial');
        \Testo\Assert::same($column->getInternalType(), 'smallserial');
        \Testo\Assert::same((string) $savedColumn->getDefaultValue(), "nextval('small_serial_foo_seq'::regclass)");
    }

    #[\Testo\Test]
    public function testSerial(): void
    {
        $schema = $this->schema('serial');

        /** @var PostgresColumn $column */
        $column = $schema->serial('foo');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('serial')->getColumns()['foo'];
        \Testo\Assert::false($this->getPrivatePropertyValue($savedColumn, 'isPrimary'));
        \Testo\Assert::false($this->getPrivatePropertyValue($column, 'isPrimary'));
        \Testo\Assert::false($savedColumn->isNullable());
        \Testo\Assert::same($column->getAbstractType(), 'serial');
        \Testo\Assert::same($column->getInternalType(), 'serial');
        \Testo\Assert::same((string) $savedColumn->getDefaultValue(), "nextval('serial_foo_seq'::regclass)");
    }

    #[\Testo\Test]
    public function testBigSerial(): void
    {
        $schema = $this->schema('big_serial');

        /** @var PostgresColumn $column */
        $column = $schema->bigSerial('foo');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('big_serial')->getColumns()['foo'];
        \Testo\Assert::false($this->getPrivatePropertyValue($savedColumn, 'isPrimary'));
        \Testo\Assert::false($this->getPrivatePropertyValue($column, 'isPrimary'));
        \Testo\Assert::false($savedColumn->isNullable());
        \Testo\Assert::same($column->getAbstractType(), 'bigserial');
        \Testo\Assert::same($column->getInternalType(), 'bigserial');
        \Testo\Assert::same((string) $savedColumn->getDefaultValue(), "nextval('big_serial_foo_seq'::regclass)");
    }
}
