<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Driver\Handler;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractTable;

abstract class ForeignKeysTest extends BaseTest
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

    public function sampleSchema(string $table): AbstractTable
    {
        $schema = $this->schema($table);

        if (!$schema->exists()) {
            $schema->primary('id');

            $schema->integer('secondary_id');
            $schema->index(['secondary_id'])->unique(true); //Index is required

            $schema->integer('secondary_id_2');
            $schema->integer('secondary_id_3');
            $schema->index(['secondary_id_2', 'secondary_id_3'])->unique(true); //Index is required

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

            $schema->save(Handler::DO_ALL);
        }

        return $schema;
    }

    public function testCreateWithReferenceToExistedTable()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('external', ['id']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));
    }

    public function testCreateWithReferenceToExistedTableWithName()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('external', ['id']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));
    }

    public function testCreateWithReferenceToExistedTableCascade()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::CASCADE)
            ->onUpdate(AbstractForeignKey::CASCADE);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));
    }

    public function testCreateWithReferenceToExistedTableNoAction()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));
    }

    public function testDropExistedReference()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->dropForeignKey(['external_id']);
        $schema->save(Handler::DO_ALL);

        $this->assertFalse($this->schema('schema')->hasForeignKey(['external_id']));
    }

    public function testChangeReferenceForeignKey()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->references('external', ['secondary_id']);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeReferenceForeignTable()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));

        $this->assertTrue($this->sampleSchema('external2')->exists());

        $schema->foreignKey(['external_id'])->references('external2', ['secondary_id']);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeUpdateRuleToCascade()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->onUpdate(AbstractForeignKey::CASCADE);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeUpdateRuleToNoAction()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::CASCADE)
            ->onUpdate(AbstractForeignKey::CASCADE);

        $schema->save(Handler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->onUpdate(AbstractForeignKey::NO_ACTION);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeDeleteRuleToCascade()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->onDelete(AbstractForeignKey::CASCADE);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeDeleteRuleToNoAction()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::CASCADE)
            ->onUpdate(AbstractForeignKey::CASCADE);

        $schema->save(Handler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->onDelete(AbstractForeignKey::NO_ACTION);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testCompositeKeys()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->integer('external_id_2');

        $schema
            ->foreignKey(['external_id', 'external_id_2'])
            ->references('external', ['secondary_id_2', 'secondary_id_3'])
            ->onDelete(AbstractForeignKey::CASCADE)
            ->onUpdate(AbstractForeignKey::CASCADE);

        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id', 'external_id_2']));

        $schema->foreignKey(['external_id', 'external_id_2'])->onDelete(AbstractForeignKey::NO_ACTION);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }
}