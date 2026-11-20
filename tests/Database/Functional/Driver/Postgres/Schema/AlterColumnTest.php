<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\AlterColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class AlterColumnTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testNativeEnums(): void
    {
        $driver = $this->database->getDriver();
        try {
            $driver->execute("CREATE TYPE mood AS ENUM ('sad', 'ok', 'happy');");
        } catch (StatementException $e) {
        }

        try {
            $driver->execute(
                'CREATE TABLE person (
    name text,
    current_mood mood
);',
            );
        } catch (StatementException $e) {
        }

        $schema = $driver->getSchema('person');
        \Testo\Assert::same($schema->column('current_mood')->getAbstractType(), 'enum');
        \Testo\Assert::same($schema->column('current_mood')->getEnumValues(), ['sad', 'ok', 'happy']);

        // convert to internal type
        $schema->save();

        $schema = $driver->getSchema('person');
        $schema->column('current_mood')->enum(['angry', 'happy']);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $driver->execute('DROP TABLE person');
        $driver->execute('DROP TYPE mood');
    }

    #[\Testo\Test]
    public function testDatetimeColumnSizeException(): void
    {
        \Testo\Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->datetime('other_datetime', -1);
        $schema->save();
    }

    #[\Testo\Test]
    public function testDatetimeColumnSize2Exception(): void
    {
        \Testo\Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->datetime('other_datetime', 7);
        $schema->save();
    }

    #[\Testo\Test]
    public function testChangeDatetimeSize(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        \Testo\Assert::same($this->fetchSchema($schema)->column('other_datetime')->getSize(), 0);

        $schema->other_datetime->datetime(6);
        $schema->save();

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->fetchSchema($schema)->column('other_datetime')->getSize(), 6);
    }

    #[\Testo\Test]
    public function testAddSerialColumn(): void
    {
        $schema = $this->sampleSchema('table');
        $schema->primary('id');

        $schema->save();
        $this->assertSameAsInDB($schema);

        $schema->serial('foo');

        $schema->save();
        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddCustomColumnTypeWithOptions(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->type('vector_column', 'vector(3)');
        $column = $schema->column('vector_column');

        \Testo\Assert::same($column->getDeclaredType(), 'vector(3)');
        \Testo\Assert::same($column->getInternalType(), 'vector(3)');
        \Testo\Assert::same($column->getType(), 'string');
        \Testo\Assert::same($column->getAbstractType(), 'unknown');
        \Testo\Assert::same($schema->column('vector_column')->sqlStatement($this->database->getDriver()), '"vector_column" vector(3) NULL');
    }
}
