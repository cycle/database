<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Feature\Common\Schema\AlterColumnTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class AlterColumnTest extends CommonClass
{
    #[Test]
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
        Assert::same($schema->column('current_mood')->getAbstractType(), 'enum');
        Assert::same($schema->column('current_mood')->getEnumValues(), ['sad', 'ok', 'happy']);

        // convert to internal type
        $schema->save();

        $schema = $driver->getSchema('person');
        $schema->column('current_mood')->enum(['angry', 'happy']);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $driver->execute('DROP TABLE person');
        $driver->execute('DROP TYPE mood');
    }

    #[Test]
    public function testDatetimeColumnSizeException(): void
    {
        Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->datetime('other_datetime', -1);
        $schema->save();
    }

    #[Test]
    public function testDatetimeColumnSize2Exception(): void
    {
        Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->datetime('other_datetime', 7);
        $schema->save();
    }

    #[Test]
    public function testChangeDatetimeSize(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        Assert::same($this->fetchSchema($schema)->column('other_datetime')->getSize(), 0);

        $schema->other_datetime->datetime(6);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->column('other_datetime')->getSize(), 6);
    }

    #[Test]
    public function testAddSerialColumn(): void
    {
        $schema = $this->sampleSchema('table');
        $schema->primary('id');

        $schema->save();
        SchemaAssert::sameAsInDb($schema);

        $schema->serial('foo');

        $schema->save();
        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
    public function testAddCustomColumnTypeWithOptions(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->type('vector_column', 'vector(3)');
        $column = $schema->column('vector_column');

        Assert::same($column->getDeclaredType(), 'vector(3)');
        Assert::same($column->getInternalType(), 'vector(3)');
        Assert::same($column->getType(), 'string');
        Assert::same($column->getAbstractType(), 'unknown');
        Assert::same($schema->column('vector_column')->sqlStatement($this->database->getDriver()), '"vector_column" vector(3) NULL');
    }
}
