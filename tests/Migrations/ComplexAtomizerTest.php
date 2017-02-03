<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Migrations;

use Spiral\Database\Schemas\Prototypes\AbstractReference;

abstract class ComplexAtomizerTest extends BaseTest
{
    public function testCreateMultiple()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);

        $schema1 = $this->schema('sample1');
        $schema1->primary('id');
        $schema1->float('value');
        $schema1->integer('sample_id');
        $schema1->foreign('sample_id')->references('sample', 'id');

        $this->atomize('migration1', [$schema, $schema1]);
        $this->migrator->run();

        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->db->hasTable('sample1'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
        $this->assertFalse($this->db->hasTable('sample1'));
    }

    public function testCreateMultipleChangeFK()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);

        $schema1 = $this->schema('sample1');
        $schema1->primary('id');
        $schema1->float('value');
        $schema1->integer('sample_id');
        $schema1->foreign('sample_id')->references('sample', 'id')
            ->onDelete(AbstractReference::CASCADE)
            ->onUpdate(AbstractReference::CASCADE);

        $this->atomize('migration1', [$schema, $schema1]);
        $this->migrator->run();

        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->db->hasTable('sample1'));

        $fk = $this->schema('sample1')->foreign('sample_id');
        $this->assertSame(AbstractReference::CASCADE, $fk->getDeleteRule());
        $this->assertSame(AbstractReference::CASCADE, $fk->getUpdateRule());

        $schema1 = $this->schema('sample1');
        $schema1->foreign('sample_id')->references('sample', 'id')
            ->onDelete(AbstractReference::NO_ACTION)
            ->onUpdate(AbstractReference::NO_ACTION);

        $this->atomize('migration1', [$this->schema('sample'), $schema1]);
        $this->migrator->run();

        $fk = $this->schema('sample1')->foreign('sample_id');
        $this->assertSame(AbstractReference::NO_ACTION, $fk->getDeleteRule());
        $this->assertSame(AbstractReference::NO_ACTION, $fk->getUpdateRule());

        $this->migrator->rollback();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->db->hasTable('sample1'));

        $fk = $this->schema('sample1')->foreign('sample_id');
        $this->assertSame(AbstractReference::CASCADE, $fk->getDeleteRule());
        $this->assertSame(AbstractReference::CASCADE, $fk->getUpdateRule());

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
        $this->assertFalse($this->db->hasTable('sample1'));
    }

    public function testCreateMultipleWithPivot()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);

        $schema1 = $this->schema('sample1');
        $schema1->primary('id');
        $schema1->float('value');
        $schema1->integer('sample_id');
        $schema1->foreign('sample_id')->references('sample', 'id');

        $schema2 = $this->schema('sample2');
        $schema2->integer('sample_id');
        $schema2->foreign('sample_id')->references('sample', 'id');
        $schema2->integer('sample1_id');
        $schema2->foreign('sample1_id')->references('sample1', 'id');

        $this->atomize('migration1', [$schema, $schema1, $schema2]);
        $this->migrator->run();

        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->db->hasTable('sample1'));
        $this->assertTrue($this->db->hasTable('sample2'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
        $this->assertFalse($this->db->hasTable('sample1'));
        $this->assertFalse($this->db->hasTable('sample2'));
    }

    public function testCreateAndAddFK()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);

        $schema1 = $this->schema('sample1');
        $schema1->primary('id');
        $schema1->float('value');

        $this->atomize('migration1', [$schema, $schema1]);
        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->db->hasTable('sample1'));

        $schema1 = $this->schema('sample1');
        $schema1->integer('sample_id');
        $schema1->foreign('sample_id')->references('sample', 'id');

        $this->atomize('migration2', [$this->schema('sample'), $schema1]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->db->hasTable('sample1'));
        $this->assertTrue($this->schema('sample1')->hasForeign('sample_id'));

        $this->migrator->rollback();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->db->hasTable('sample1'));
        $this->assertFalse($this->schema('sample1')->hasForeign('sample_id'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
        $this->assertFalse($this->db->hasTable('sample1'));
    }
}