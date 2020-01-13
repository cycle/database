<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Schema\AbstractTable;

abstract class ConsistencyTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp(): void
    {
        $this->database = $this->db();
    }

    public function tearDown(): void
    {
        $this->dropDatabase($this->db());
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testPrimary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->primary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testBigPrimary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->bigPrimary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testInteger(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->integer('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testBigInteger(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->bigInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testString(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testStringWithLength(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target', 100);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testFloat(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->float('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDouble(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->double('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDecimalOneZero(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->decimal('target', 1, 0);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDecimalFiveTwo(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->decimal('target', 5, 2);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testBoolean(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->boolean('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testEnum(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'value']);

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testJson(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->json('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTinyInteger(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->tinyInteger('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testText(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->text('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTinyText(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->tinyText('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTextLongText(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->longText('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testBinary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->binary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTinyBinary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->tinyBinary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testLongBinary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->longBinary('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTimestamp(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->timestamp('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDatetime(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->datetime('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testDate(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->date('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testTime(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->time('target');

        $schema->save();
        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testUuid(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->uuid('target');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertSame('string', $schema->column('target')->getType());

        $this->database->table('table')->insertOne(
            [
                'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'
            ]
        );

        $this->assertEquals(
            [
                'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'
            ],
            $this->database->table('table')->select()->fetchAll()[0]
        );
    }

    public function testUuidPrimary(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->uuid('target')->nullable(false);
        $schema->setPrimaryKeys(['target']);
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertTrue($schema->column('target')->compare($column));
        $this->assertSame('string', $schema->column('target')->getType());
        $this->assertSame(['target'], $schema->getPrimaryKeys());

        $this->database->table('table')->insertOne(
            [
                'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'
            ]
        );

        $this->assertEquals(
            [
                'target' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'
            ],
            $this->database->table('table')->select()->fetchAll()[0]
        );
    }
}
