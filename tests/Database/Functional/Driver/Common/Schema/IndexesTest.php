<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class IndexesTest extends BaseTest
{
    #[\Testo\Test]
    public function testCreateWithIndex(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::false($this->schema('table')->index(['value'])->isUnique());
    }

    #[\Testo\Test]
    public function testCreateWithUniqueIndex(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        \Testo\Assert::true($this->schema('table')->index(['value'])->isUnique());
    }

    #[\Testo\Test]
    public function testCreateWithComplexIndex(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->string('subset', 2);
        $schema->index(['value', 'subset']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testCreateOrderedIndex(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->string('subset', 2);
        $schema->index(['subset', 'value DESC']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testCreateWithComplexUniqueIndex(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('value');
        $schema->string('subset', 2);
        $schema->index(['value', 'subset'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameIndexThoughTable(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

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

        \Testo\Assert::same($this->fetchSchema($schema)->index(['value', 'subset'])->getName(), 'new_index_name');
    }

    #[\Testo\Test]
    public function testCreateWithMultipleIndexesAndEnum(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->enum('status', ['active', 'disabled']);
        $schema->index(['status']);

        $schema->integer('value');
        $schema->string('subset', 2);
        $schema->index(['value', 'subset'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['balance']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testNamedAddIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['balance'])->setName('index_for_balance');

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddUniqueIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['email'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddUniqueIndexToEnum(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['status']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddColumnAndComplexIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->string('password');
        $schema->index(['email', 'password']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddColumnAndComplexUniqueIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->string('password');
        $schema->index(['email', 'password'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddIndexToDatetime(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['datetime']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDropIndex(): void
    {
        $schema = $this->sampleSchemaWithIndexes('table');
        \Testo\Assert::true($schema->exists());

        $schema->dropIndex(['email']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDropComplexIndex(): void
    {
        $schema = $this->sampleSchemaWithIndexes('table');
        \Testo\Assert::true($schema->exists());

        $schema->dropIndex(['email', 'status']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDropOrderedIndex(): void
    {
        $schema = $this->sampleSchemaWithIndexes('table');

        $schema->index(['id', 'balance DESC']);
        $schema->save(Handler::DO_ALL);
        \Testo\Assert::true($schema->exists());

        $schema->dropIndex(['id', 'balance DESC']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testDropMultipleIndexes(): void
    {
        $schema = $this->sampleSchemaWithIndexes('table');
        \Testo\Assert::true($schema->exists());

        $schema->dropIndex(['email', 'status']);
        $schema->dropIndex(['balance']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['balance'])->setName('new_balance_name');

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeIndexToUnique(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['balance'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeIndexToNonUnique(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['email'])->unique(false);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeIndexToNonUniqueAndRename(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['email'])->unique(false)->setName('non_unique_email');

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeComplexIndexToUnique(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['email', 'status'])->unique(true);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRemoveColumnFromComplexIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['email', 'status'])->columns(['status']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testAddColumnFromComplexIndexAndRename(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['email', 'status'])->columns(['email', 'status', 'flagged'])->setName(
            '3d_index',
        );

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testRenameColumnWithAddedIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['email', 'status']);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $schema = $this->fetchSchema($schema);
        $schema->renameColumn('status', 'new_status');
        $schema->save();

        $schema = $this->fetchSchema($schema);
        //  print_r($schema);
        \Testo\Assert::true($schema->hasIndex(['email', 'new_status']));
    }

    #[\Testo\Test]
    public function testChangeColumnTypeWithAttachedIndex(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->index(['email', 'value']);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        \Testo\Assert::true($this->fetchSchema($schema)->hasIndex(['email', 'value']));

        $schema = $this->fetchSchema($schema);
        $schema->column('value')->bigInteger();
        $schema->save();

        $schema = $this->fetchSchema($schema);
        \Testo\Assert::true($schema->hasIndex(['email', 'value']));
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
}
