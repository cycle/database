<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[ExceptDriver(Driver::MySQL, Driver::Postgres)]
class ConsistencyTest extends DatabaseTestCase
{
    #[Test]
    public function testPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->primary('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testBigPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->bigPrimary('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testInteger(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->integer('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testBigInteger(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->bigInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testString(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->string('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testStringWithLength(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->string('target', 100);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testFloat(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->float('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDouble(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->double('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDecimalOneZero(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->decimal('target', 1, 0);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDecimalFiveTwo(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->decimal('target', 5, 2);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testBoolean(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->boolean('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testEnum(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'value']);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testEnumWithSingleValue(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->enum('target', ['catalog']);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getEnumValues(), ['catalog']);
    }

    #[Test]
    public function testJson(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->json('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testTinyInteger(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->tinyInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testSmallInteger(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->smallInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testText(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->text('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testTinyText(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->tinyText('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testMediumText(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->mediumText('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testTextLongText(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->longText('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testBinary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->binary('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testTinyBinary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->tinyBinary('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testLongBinary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->longBinary('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testTimestamp(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->timestamp('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDatetime(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->datetime('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDate(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->date('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testTime(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->time('target');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testSnowflake(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->snowflake('target');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'int');

        $this->database->table('table')->insertOne(
            [
                'target' => 7340580095540599922,
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => 7340580095540599922,
        ]);
    }

    #[Test]
    public function testSnowflakeCallingColumnMethod(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->column('target')->snowflake();

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'int');

        $this->database->table('table')->insertOne(
            [
                'target' => 7340580095540599922,
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => 7340580095540599922,
        ]);
    }

    #[Test]
    public function testSnowflakePrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->snowflake('target')->nullable(false);
        $schema->setPrimaryKeys(['target']);
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'int');
        Assert::same($schema->getPrimaryKeys(), ['target']);

        $this->database->table('table')->insertOne(
            [
                'target' => 7340580095540599922,
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => 7340580095540599922,
        ]);
    }

    #[Test]
    public function testUlid(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->ulid('target');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'string');

        $this->database->table('table')->insertOne(
            [
                'target' => '0GWWXY2G84DFMRVWQNJ1SRYCMC',
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => '0GWWXY2G84DFMRVWQNJ1SRYCMC',
        ]);
    }

    #[Test]
    public function testUlidCallingColumnMethod(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->column('target')->ulid();

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'string');

        $this->database->table('table')->insertOne(
            [
                'target' => '0GWWXY2G84DFMRVWQNJ1SRYCMC',
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => '0GWWXY2G84DFMRVWQNJ1SRYCMC',
        ]);
    }

    #[Test]
    public function testUlidPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->ulid('target')->nullable(false);
        $schema->setPrimaryKeys(['target']);
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'string');
        Assert::same($schema->getPrimaryKeys(), ['target']);

        $this->database->table('table')->insertOne(
            [
                'target' => '0GWWXY2G84DFMRVWQNJ1SRYCMC',
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => '0GWWXY2G84DFMRVWQNJ1SRYCMC',
        ]);
    }

    #[Test]
    public function testUuid(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->uuid('target');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'string');

        $this->database->table('table')->insertOne(
            [
                'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ]);
    }

    #[Test]
    public function testUuidCallingColumnMethod(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->column('target')->uuid();

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'string');

        $this->database->table('table')->insertOne(
            [
                'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ]);
    }

    #[Test]
    public function testUuidPrimary(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->uuid('target')->nullable(false);
        $schema->setPrimaryKeys(['target']);
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        Assert::true($schema->column('target')->compare($column));
        Assert::same($schema->column('target')->getType(), 'string');
        Assert::same($schema->getPrimaryKeys(), ['target']);

        $this->database->table('table')->insertOne(
            [
                'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            ],
        );

        Assert::equals($this->database->table('table')->select()->fetchAll()[0], [
            'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ]);
    }
}
