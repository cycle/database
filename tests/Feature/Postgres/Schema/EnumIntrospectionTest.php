<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Exception\StatementException;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Lifecycle\AfterTest;
use Testo\Test;

/**
 * Native enums and enums emulated via a CHECK constraint are resolved with two batched queries,
 * so their combinations in a single table need explicit coverage.
 */
final class EnumIntrospectionTest extends DatabaseTestCase
{
    #[Test]
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
        Assert::same($schema->column('current_mood')->getAbstractType(), 'enum');
        Assert::same($schema->column('current_mood')->getEnumValues(), ['sad', 'ok', 'happy']);

        Assert::same($schema->column('forecast')->getAbstractType(), 'enum');
        Assert::same($schema->column('forecast')->getEnumValues(), ['rain', 'sun']);

        // Enum emulated via a CHECK constraint
        Assert::same($schema->column('status')->getAbstractType(), 'enum');
        Assert::same($schema->column('status')->getEnumValues(), ['active', 'disabled']);

        // A varchar without a constraint must stay a plain string
        Assert::same($schema->column('plain_string')->getAbstractType(), 'string');
        Assert::same($schema->column('plain_string')->getEnumValues(), []);

        Assert::same($schema->getPrimaryKeys(), ['id']);
    }

    /**
     * The enum type of a column must be resolved by both the type name and the type schema:
     * same-named enum types from other schemas must not interfere, and a type outside of the
     * `search_path` must still be resolvable.
     */
    #[Test]
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

        Assert::same($schema->column('public_mood')->getAbstractType(), 'enum');
        Assert::same($schema->column('public_mood')->getEnumValues(), ['sad', 'ok', 'happy']);

        Assert::same($schema->column('foreign_mood')->getAbstractType(), 'enum');
        Assert::same($schema->column('foreign_mood')->getEnumValues(), ['angry', 'calm']);
    }

    /**
     * Labels are read from `pg_enum` one row per label, so values with commas, quotes or spaces
     * are returned verbatim. The former `enum_range()` parsing split the textual range by a comma
     * and mangled such labels.
     */
    #[Test]
    public function testLabelsWithSpecialCharacters(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute(
            "CREATE TYPE dirty_labels AS ENUM ('it''s', 'a,b', 'with \"quotes\"', ' spaced ')",
        );
        $driver->execute(
            'CREATE TABLE mixed_enums (
                id serial NOT NULL,
                label dirty_labels,
                CONSTRAINT mixed_enums_pkey PRIMARY KEY (id)
            )',
        );

        $schema = $driver->getSchema('mixed_enums');

        Assert::same($schema->column('label')->getAbstractType(), 'enum');
        Assert::same($schema->column('label')->getEnumValues(), ["it's", 'a,b', 'with "quotes"', ' spaced ']);
    }

    /**
     * An enum type without labels has no values, so the column must not be reported as an enum.
     * The former `enum_range()` parsing produced a single empty-string value for it.
     */
    #[Test]
    public function testEmptyEnumType(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute('CREATE TYPE empty_enum AS ENUM ()');
        $driver->execute(
            'CREATE TABLE mixed_enums (
                id serial NOT NULL,
                hollow empty_enum,
                CONSTRAINT mixed_enums_pkey PRIMARY KEY (id)
            )',
        );

        $schema = $driver->getSchema('mixed_enums');

        Assert::notSame($schema->column('hollow')->getAbstractType(), 'enum');
        Assert::same($schema->column('hollow')->getEnumValues(), []);
    }

    #[Test]
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

        Assert::same($schema->getPrimaryKeys(), ['left_id', 'right_id']);
        Assert::same($schema->column('status')->getEnumValues(), ['active', 'disabled']);
        // The composite PK constraint must not be reported as a regular index
        Assert::same($schema->getIndexes(), []);
    }

    #[Test]
    public function testNativeEnumWithDefaultReadInBulk(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute("CREATE TYPE mood AS ENUM ('sad', 'ok', 'happy')");
        $driver->execute(
            "CREATE TABLE mixed_enums (
                id serial NOT NULL,
                current_mood mood NOT NULL DEFAULT 'ok',
                CONSTRAINT mixed_enums_pkey PRIMARY KEY (id)
            )",
        );

        $schema = $this->database->getSchemas(['mixed_enums'])['mixed_enums'];

        Assert::same($schema->column('current_mood')->getEnumValues(), ['sad', 'ok', 'happy']);
        Assert::same($schema->column('current_mood')->getDefaultValue(), 'ok');
    }

    #[AfterTest]
    public function tearDown(): void
    {
        $driver = $this->database->getDriver();

        foreach (['mixed_enums'] as $table) {
            try {
                $driver->execute("DROP TABLE IF EXISTS {$table}");
            } catch (StatementException) {
            }
        }

        foreach (['mood', 'weather', 'dirty_labels', 'empty_enum'] as $type) {
            try {
                $driver->execute("DROP TYPE IF EXISTS {$type}");
            } catch (StatementException) {
            }
        }

        try {
            $driver->execute('DROP SCHEMA IF EXISTS enum_intro CASCADE');
        } catch (StatementException) {
        }

    }
}
