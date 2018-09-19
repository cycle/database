<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Entity\AbstractHandler;
use Spiral\Database\Entity\Database;
use Spiral\Database\Schema\Prototypes\AbstractColumn;
use Spiral\Database\Schema\Prototypes\AbstractReference;
use Spiral\Database\Schema\Prototypes\AbstractTable;

abstract class ForeignKeysTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->database();
    }

    public function tearDown()
    {
        $this->dropAll($this->database());
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

            $schema->save(AbstractHandler::DO_ALL);
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
        $schema->foreign('external_id')->references('external', 'id');

        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));
    }

    public function testCreateWithReferenceToExistedTableCascade()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::CASCADE)
            ->onUpdate(AbstractReference::CASCADE);

        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));
    }

    public function testCreateWithReferenceToExistedTableNoAction()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::NO_ACTION)
            ->onUpdate(AbstractReference::NO_ACTION);

        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));
    }

    public function testDropExistedReference()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::NO_ACTION)
            ->onUpdate(AbstractReference::NO_ACTION);

        $schema->save(AbstractHandler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));

        $schema->dropForeign('external_id');
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertFalse($this->schema('schema')->hasForeign('external_id'));
    }

    public function testChangeReferenceForeignKey()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::NO_ACTION)
            ->onUpdate(AbstractReference::NO_ACTION);

        $schema->save(AbstractHandler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));

        $schema->foreign('external_id')->references('external', 'secondary_id');
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeReferenceForeignTable()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::NO_ACTION)
            ->onUpdate(AbstractReference::NO_ACTION);

        $schema->save(AbstractHandler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));

        $this->assertTrue($this->sampleSchema('external2')->exists());

        $schema->foreign('external_id')->references('external2', 'secondary_id');
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeUpdateRuleToCascade()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::NO_ACTION)
            ->onUpdate(AbstractReference::NO_ACTION);

        $schema->save(AbstractHandler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));

        $schema->foreign('external_id')->onUpdate(AbstractReference::CASCADE);
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeUpdateRuleToNoAction()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::CASCADE)
            ->onUpdate(AbstractReference::CASCADE);

        $schema->save(AbstractHandler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));

        $schema->foreign('external_id')->onUpdate(AbstractReference::NO_ACTION);
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeDeleteRuleToCascade()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::NO_ACTION)
            ->onUpdate(AbstractReference::NO_ACTION);

        $schema->save(AbstractHandler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));

        $schema->foreign('external_id')->onDelete(AbstractReference::CASCADE);
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    public function testChangeDeleteRuleToNoAction()
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreign('external_id')->references('external', 'id')
            ->onDelete(AbstractReference::CASCADE)
            ->onUpdate(AbstractReference::CASCADE);

        $schema->save(AbstractHandler::DO_ALL);
        $this->assertTrue($this->schema('schema')->hasForeign('external_id'));

        $schema->foreign('external_id')->onDelete(AbstractReference::NO_ACTION);
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }
}