<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Spiral\Database\Driver\Handler;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractTable;

abstract class IndexesTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->db();
    }

    public function tearDown()
    {
        $this->dropDatabase($this->db());
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    protected function sampleSchema(string $table): AbstractTable
    {
        $schema = $this->schema($table);

        if (!$schema->exists()) {
            $schema->primary('id');
            $schema->string('first_name')->nullable(false);
            $schema->string('last_name')->nullable(false);
            $schema->string('email', 64)->nullable(false);
            $schema->enum('status', ['active', 'disabled'])->defaultValue('active');
            $schema->double('balance')->defaultValue(0);
            $schema->boolean('flagged')->defaultValue(true);
            $schema->integer('value');
            $schema->text('bio');

            //Some dates
            $schema->timestamp('timestamp')->defaultValue(AbstractColumn::DATETIME_NOW);
            $schema->datetime('datetime')->defaultValue('2017-01-01 00:00:00');
            $schema->date('datetime')->nullable(true);
            $schema->time('datetime')->defaultValue('00:00');

            $schema->save(Handler::DO_ALL);
        }

        return $schema;
    }

    protected function sampleSchemaWithIndexes(string $table): AbstractTable
    {
        $schema = $this->schema($table);

        if (!$schema->exists()) {
            $schema->primary('id');
            $schema->string('first_name')->nullable(false);
            $schema->string('last_name')->nullable(false);
            $schema->string('email', 64)->nullable(false);
            $schema->enum('status', ['active', 'disabled'])->defaultValue('active');
            $schema->double('balance')->defaultValue(0);
            $schema->boolean('flagged')->defaultValue(true);

            $schema->text('bio');

            //Some dates
            $schema->timestamp('timestamp')->defaultValue(AbstractColumn::DATETIME_NOW);
            $schema->datetime('datetime')->defaultValue('2017-01-01 00:00:00');
            $schema->date('datetime')->nullable(true);
            $schema->time('datetime')->defaultValue('00:00');

            $schema->index(['email'])->unique(true);
            $schema->index(['email', 'status']);
            $schema->index(['balance']);

            $schema->save(Handler::DO_ALL);
        }

        return $schema;
    }

    public function testCreateWithIndex()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertFalse($this->schema('table')->index(['value'])->isUnique());
    }

    public function testCreateWithUniqueIndex()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertTrue($this->schema('table')->index(['value'])->isUnique());
    }

    public function testCreateWithComplexIndex()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->string('subset', 2);
        $schema->index(['value', 'subset']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testCreateWithComplexUniqueIndex()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->string('subset', 2);
        $schema->index(['value', 'subset'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testRenameIndexThoughTable()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->string('subset', 2);
        $schema->index(['value', 'subset'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $schema = $this->fetchSchema($schema);
        $schema->renameIndex(['value', 'subset'], 'new_index_name');
        $schema->save();
        $this->assertSameAsInDB($schema);

        $this->assertSame('new_index_name', $this->fetchSchema($schema)->index(['value', 'subset'])->getName());
    }

    public function testCreateWithMultipleIndexesAndEnum()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->enum('status', ['active', 'disabled']);
        $schema->index(['status']);

        $schema->integer('value');
        $schema->string('subset', 2);
        $schema->index(['value', 'subset'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testAddIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['balance']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testNamedAddIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['balance'])->setName('index_for_balance');

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testAddUniqueIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['email'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testAddUniqueIndexToEnum()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['status']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnAndComplexIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('password');
        $schema->index(['email', 'password']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnAndComplexUniqueIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->string('password');
        $schema->index(['email', 'password'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testAddIndexToDatetime()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['datetime']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testDropIndex()
    {
        $schema = $this->sampleSchemaWithIndexes('table');
        $this->assertTrue($schema->exists());

        $schema->dropIndex(['email']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testDropComplexIndex()
    {
        $schema = $this->sampleSchemaWithIndexes('table');
        $this->assertTrue($schema->exists());

        $schema->dropIndex(['email', 'status']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testDropMultipleIndexes()
    {
        $schema = $this->sampleSchemaWithIndexes('table');
        $this->assertTrue($schema->exists());

        $schema->dropIndex(['email', 'status']);
        $schema->dropIndex(['balance']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testRenameIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['balance'])->setName('new_balance_name');

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeIndexToUnique()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['balance'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeIndexToNonUnique()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['email'])->unique(false);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeIndexToNonUniqueAndRename()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['email'])->unique(false)->setName('non_unique_email');

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeComplexIndexToUnique()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['email', 'status'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testRemoveColumnFromComplexIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['email', 'status'])->columns(['status']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testAddColumnFromComplexIndexAndRename()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['email', 'status'])->columns(['email', 'status', 'flagged'])->setName(
            "3d_index"
        );

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testRenameColumnWithAddedIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['email', 'status']);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $schema = $this->fetchSchema($schema);
        $schema->renameColumn('status', 'new_status');
        $schema->save();

        $schema = $this->fetchSchema($schema);
        //  print_r($schema);
        $this->assertTrue($schema->hasIndex(['email', 'new_status']));
    }

    public function testChangeColumnTypeWithAttachedIndex()
    {
        $schema = $this->sampleSchema('table');
        $this->assertTrue($schema->exists());

        $schema->index(['email', 'value']);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->fetchSchema($schema)->hasIndex(['email', 'value']));

        $schema = $this->fetchSchema($schema);
        $schema->column('value')->bigInteger();
        $schema->save();

        $schema = $this->fetchSchema($schema);
        $this->assertTrue($schema->hasIndex(['email', 'value']));
    }
}