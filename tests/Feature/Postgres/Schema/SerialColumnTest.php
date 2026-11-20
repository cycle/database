<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class SerialColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testSmallSerial(): void
    {
        $schema = $this->schema('small_serial');

        /** @var PostgresColumn $column */
        $column = $schema->smallSerial('foo');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('small_serial')->getColumns()['foo'];
        Assert::false((new \ReflectionProperty($savedColumn, 'isPrimary'))->getValue($savedColumn));
        Assert::false((new \ReflectionProperty($column, 'isPrimary'))->getValue($column));
        Assert::false($savedColumn->isNullable());
        Assert::same($column->getAbstractType(), 'smallserial');
        Assert::same($column->getInternalType(), 'smallserial');
        Assert::same((string) $savedColumn->getDefaultValue(), "nextval('small_serial_foo_seq'::regclass)");
    }

    #[Test]
    public function testSerial(): void
    {
        $schema = $this->schema('serial');

        /** @var PostgresColumn $column */
        $column = $schema->serial('foo');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('serial')->getColumns()['foo'];
        Assert::false((new \ReflectionProperty($savedColumn, 'isPrimary'))->getValue($savedColumn));
        Assert::false((new \ReflectionProperty($column, 'isPrimary'))->getValue($column));
        Assert::false($savedColumn->isNullable());
        Assert::same($column->getAbstractType(), 'serial');
        Assert::same($column->getInternalType(), 'serial');
        Assert::same((string) $savedColumn->getDefaultValue(), "nextval('serial_foo_seq'::regclass)");
    }

    #[Test]
    public function testBigSerial(): void
    {
        $schema = $this->schema('big_serial');

        /** @var PostgresColumn $column */
        $column = $schema->bigSerial('foo');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $savedColumn = $this->schema('big_serial')->getColumns()['foo'];
        Assert::false((new \ReflectionProperty($savedColumn, 'isPrimary'))->getValue($savedColumn));
        Assert::false((new \ReflectionProperty($column, 'isPrimary'))->getValue($column));
        Assert::false($savedColumn->isNullable());
        Assert::same($column->getAbstractType(), 'bigserial');
        Assert::same($column->getInternalType(), 'bigserial');
        Assert::same((string) $savedColumn->getDefaultValue(), "nextval('big_serial_foo_seq'::regclass)");
    }
}
