<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Postgres\Exception\PostgresException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DefaultValueTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class DefaultValueTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testJsonDefaultValueEmpty(): void
    {
        $this->expectException(PostgresException::class);
        $this->expectExceptionMessage(
            'Column `public.table.target` of type json/jsonb has an invalid default json value.'
        );
        parent::testJsonDefaultValueEmpty();
    }

    public function testJsonDefaultValueString(): void
    {
        $this->expectException(PostgresException::class);
        $this->expectExceptionMessage(
            'Column `public.table.target` of type json/jsonb has an invalid default json value.'
        );
        parent::testJsonDefaultValueString();
    }

    public function testJsonDefaultValueValidJsonString(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->json('target')->defaultValue('{"foo":"bar","baz":100.5}');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testJsonDefaultValueArray(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->json('target')->defaultValue(['foo' => 'bar', 'baz' => 100.5]);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }
}
