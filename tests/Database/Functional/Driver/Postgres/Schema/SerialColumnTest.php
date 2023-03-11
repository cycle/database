<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-postgres
 */
final class SerialColumnTest extends BaseTest
{
    public const DRIVER = 'postgres';

    public function testSmallSerial(): void
    {
        $schema = $this->schema('small_serial');

        /** @var PostgresColumn $column */
        $column = $schema->smallSerial('foo');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('small_serial')->getColumns()['foo'];
        $this->assertFalse($column->getAttributes()['primary']);
        $this->assertFalse($savedColumn->isNullable());
        $this->assertSame('smallserial', $column->getAbstractType());
        $this->assertSame('smallserial', $column->getInternalType());
        $this->assertSame(
            "nextval('small_serial_foo_seq'::regclass)",
            (string) $savedColumn->getDefaultValue()
        );
    }

    public function testSerial(): void
    {
        $schema = $this->schema('serial');

        /** @var PostgresColumn $column */
        $column = $schema->serial('foo');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('serial')->getColumns()['foo'];
        $this->assertFalse($column->getAttributes()['primary']);
        $this->assertFalse($savedColumn->isNullable());
        $this->assertSame('serial', $column->getAbstractType());
        $this->assertSame('serial', $column->getInternalType());
        $this->assertSame("nextval('serial_foo_seq'::regclass)", (string) $savedColumn->getDefaultValue());
    }

    public function testBigSerial(): void
    {
        $schema = $this->schema('big_serial');

        /** @var PostgresColumn $column */
        $column = $schema->bigSerial('foo');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $savedColumn = $this->schema('big_serial')->getColumns()['foo'];
        $this->assertFalse($column->getAttributes()['primary']);
        $this->assertFalse($savedColumn->isNullable());
        $this->assertSame('bigserial', $column->getAbstractType());
        $this->assertSame('bigserial', $column->getInternalType());
        $this->assertSame("nextval('big_serial_foo_seq'::regclass)", (string) $savedColumn->getDefaultValue());
    }
}
