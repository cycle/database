<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\AlterColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class AlterColumnTest extends CommonClass
{
    public const DRIVER = 'postgres';

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
);'
            );
        } catch (StatementException $e) {
        }

        $schema = $driver->getSchema('person');
        $this->assertSame('enum', $schema->column('current_mood')->getAbstractType());
        $this->assertSame(['sad', 'ok', 'happy'], $schema->column('current_mood')->getEnumValues());

        // convert to internal type
        $schema->save();

        $schema = $driver->getSchema('person');
        $schema->column('current_mood')->enum(['angry', 'happy']);
        $schema->save();

        $this->assertSameAsInDB($schema);

        $driver->execute('DROP TABLE person');
        $driver->execute('DROP TYPE mood');
    }

    public function testDatetimeColumnSizeException(): void
    {
        $this->expectException(SchemaException::class);
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->datetime('datetime', -1);
        $schema->save();
    }

    public function testDatetimeColumnSize2Exception(): void
    {
        $this->expectException(SchemaException::class);
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->datetime('datetime', 7);
        $schema->save();
    }

    public function testChangeDatetimeSize(): void
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $this->assertSame(0, $this->fetchSchema($schema)->column('datetime')->getSize());

        $schema->datetime->string(6);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(6, $this->fetchSchema($schema)->column('datetime')->getSize());
    }
}
