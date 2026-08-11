<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * Native enums and enums emulated via a CHECK constraint are resolved with two batched queries,
 * so their combinations in a single table need explicit coverage.
 *
 * @group driver
 * @group driver-postgres
 */
class EnumIntrospectionTest extends BaseTest
{
    public const DRIVER = 'postgres';

    public function testNativeAndEmulatedEnumsInSingleTable(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute("CREATE TYPE mood AS ENUM ('sad', 'ok', 'happy')");
        $driver->execute("CREATE TYPE weather AS ENUM ('rain', 'sun')");
        $driver->execute(
            'CREATE TABLE mixed_enums (
                id serial NOT NULL,
                name text,
                current_mood mood,
                forecast weather,
                plain_string character varying(64),
                status character varying(16),
                CONSTRAINT mixed_enums_status_check CHECK (status IN (\'active\', \'disabled\')),
                CONSTRAINT mixed_enums_pkey PRIMARY KEY (id)
            )',
        );

        $schema = $driver->getSchema('mixed_enums');

        // Two different native enum types
        $this->assertSame('enum', $schema->column('current_mood')->getAbstractType());
        $this->assertSame(['sad', 'ok', 'happy'], $schema->column('current_mood')->getEnumValues());

        $this->assertSame('enum', $schema->column('forecast')->getAbstractType());
        $this->assertSame(['rain', 'sun'], $schema->column('forecast')->getEnumValues());

        // Enum emulated via a CHECK constraint
        $this->assertSame('enum', $schema->column('status')->getAbstractType());
        $this->assertSame(['active', 'disabled'], $schema->column('status')->getEnumValues());

        // A varchar without a constraint must stay a plain string
        $this->assertSame('string', $schema->column('plain_string')->getAbstractType());
        $this->assertSame([], $schema->column('plain_string')->getEnumValues());

        $this->assertSame(['id'], $schema->getPrimaryKeys());
    }

    /**
     * The enum type of a column must be resolved by both the type name and the type schema:
     * same-named enum types from other schemas must not interfere, and a type outside of the
     * `search_path` must still be resolvable.
     */
    public function testSameNamedEnumsInDifferentSchemas(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute('CREATE SCHEMA enum_intro');
        $driver->execute("CREATE TYPE mood AS ENUM ('sad', 'ok', 'happy')");
        $driver->execute("CREATE TYPE enum_intro.mood AS ENUM ('angry', 'calm')");
        $driver->execute(
            'CREATE TABLE mixed_enums (
                id serial NOT NULL,
                public_mood mood,
                foreign_mood enum_intro.mood,
                CONSTRAINT mixed_enums_pkey PRIMARY KEY (id)
            )',
        );

        $schema = $driver->getSchema('mixed_enums');

        $this->assertSame('enum', $schema->column('public_mood')->getAbstractType());
        $this->assertSame(['sad', 'ok', 'happy'], $schema->column('public_mood')->getEnumValues());

        $this->assertSame('enum', $schema->column('foreign_mood')->getAbstractType());
        $this->assertSame(['angry', 'calm'], $schema->column('foreign_mood')->getEnumValues());
    }

    public function testCompositePrimaryKeyWithEmulatedEnum(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute(
            'CREATE TABLE mixed_enums (
                left_id integer NOT NULL,
                right_id integer NOT NULL,
                status character varying(16),
                CONSTRAINT mixed_enums_status_check CHECK (status IN (\'active\', \'disabled\')),
                CONSTRAINT mixed_enums_pkey PRIMARY KEY (left_id, right_id)
            )',
        );

        $schema = $driver->getSchema('mixed_enums');

        $this->assertSame(['left_id', 'right_id'], $schema->getPrimaryKeys());
        $this->assertSame(['active', 'disabled'], $schema->column('status')->getEnumValues());
        // The composite PK constraint must not be reported as a regular index
        $this->assertSame([], $schema->getIndexes());
    }

    public function tearDown(): void
    {
        $driver = $this->database->getDriver();

        foreach (['mixed_enums'] as $table) {
            try {
                $driver->execute("DROP TABLE IF EXISTS {$table}");
            } catch (StatementException) {
            }
        }

        foreach (['mood', 'weather'] as $type) {
            try {
                $driver->execute("DROP TYPE IF EXISTS {$type}");
            } catch (StatementException) {
            }
        }

        try {
            $driver->execute('DROP SCHEMA IF EXISTS enum_intro CASCADE');
        } catch (StatementException) {
        }

        parent::tearDown();
    }
}
