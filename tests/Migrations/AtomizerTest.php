<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Migrations;

use Spiral\Migrations\Migration;

abstract class AtomizerTest extends BaseTest
{
    public function testCreateAndDiff()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);

        $this->atomize('migration1', [$schema]);
        $migration = $this->migrator->run();

        $this->assertInstanceOf(Migration::class, $migration);
        $this->assertSame(Migration\State::STATUS_EXECUTED, $migration->getState()->getStatus());
        $this->assertInstanceOf(\DateTime::class, $migration->getState()->getTimeCreated());
        $this->assertInstanceOf(\DateTime::class, $migration->getState()->getTimeExecuted());

        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testCreateAndThenUpdate()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertSame('integer', $this->schema('sample')->column('value')->abstractType());

        $schema = $this->schema('sample');
        $schema->float('value');
        $this->atomize('migration2', [$schema]);

        $this->migrator->run();
        $this->assertSame('float', $this->schema('sample')->column('value')->abstractType());
        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertSame('integer', $this->schema('sample')->column('value')->abstractType());
        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testCreateAndThenRenameColumn()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->schema('sample')->hasColumn('value'));

        $schema = $this->schema('sample');
        $schema->integer('value')->setName('value2');
        $this->atomize('migration2', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasColumn('value2'));
        $this->assertFalse($this->schema('sample')->hasColumn('value'));

        $this->migrator->rollback();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasColumn('value'));
        $this->assertFalse($this->schema('sample')->hasColumn('value2'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testCreateAndThenRenameColumnWithIndex()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->schema('sample')->hasColumn('value'));

        $schema = $this->schema('sample');

        $schema->integer('value')->setName('value2');
        $this->atomize('migration2', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasColumn('value2'));
        $this->assertFalse($this->schema('sample')->hasColumn('value'));

        $this->migrator->rollback();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasColumn('value'));
        $this->assertFalse($this->schema('sample')->hasColumn('value2'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testCreateAndThenUpdateAddDefault()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertSame('integer', $this->schema('sample')->column('value')->abstractType());

        $schema = $this->schema('sample');
        $schema->float('value')->defaultValue(2);

        $this->atomize('migration2', [$schema]);

        $this->migrator->run();
        $this->assertSame('float', $this->schema('sample')->column('value')->abstractType());
        $this->assertEquals(2, $this->schema('sample')->column('value')->getDefaultValue());

        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertSame('integer', $this->schema('sample')->column('value')->abstractType());
        $this->assertSame(null, $this->schema('sample')->column('value')->getDefaultValue());

        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testCreateAndTThenAddIndexAndMakeUnique()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertFalse($this->schema('sample')->hasIndex(['value']));

        $schema = $this->schema('sample');
        $schema->index(['value']);

        $this->atomize('migration2', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasIndex(['value']));
        $this->assertFalse($this->schema('sample')->index(['value'])->isUnique());

        $schema = $this->schema('sample');
        $schema->index(['value'])->unique(true);

        $this->atomize('migration3', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasIndex(['value']));
        $this->assertTrue($this->schema('sample')->index(['value'])->isUnique());

        $this->migrator->rollback();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasIndex(['value']));
        $this->assertFalse($this->schema('sample')->index(['value'])->isUnique());

        $this->migrator->rollback();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertFalse($this->schema('sample')->hasIndex(['value']));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testCreateAndDropIndex()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasIndex(['value']));

        $schema = $this->schema('sample');
        $schema->dropIndex(['value']);

        $this->atomize('migration2', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertFalse($this->schema('sample')->hasIndex(['value']));

        $this->migrator->rollback();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasIndex(['value']));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testCreateAndDropColumn()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->integer('value');
        $schema->index(['value']);
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasIndex(['value']));

        $schema = $this->schema('sample');
        $schema->dropColumn('value');
        $schema->string('name', 120);

        $this->atomize('migration2', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertFalse($this->schema('sample')->hasColumn('value'));
        $this->assertTrue($this->schema('sample')->hasColumn('name'));

        $this->migrator->rollback();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertTrue($this->schema('sample')->hasColumn('value'));
        $this->assertFalse($this->schema('sample')->hasColumn('name'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testSetPrimaryKeys()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->integer('id')->nullable(false);
        $schema->integer('value')->nullable(false);
        $schema->setPrimaryKeys(['id', 'value']);

        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertSame(['id', 'value'], $this->schema('sample')->getPrimaryKeys());

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    /**
     * @expectedException \Spiral\Migrations\Exceptions\Operations\TableException
     */
    public function testChangePrimaryKeys()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->integer('id')->nullable(false);
        $schema->integer('value')->nullable(false);
        $schema->setPrimaryKeys(['id', 'value']);

        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertTrue($this->db->hasTable('sample'));
        $this->assertSame(['id', 'value'], $this->schema('sample')->getPrimaryKeys());

        $schema = $this->schema('sample');
        $schema->dropColumn('value');
        $schema->setPrimaryKeys(['id']);

        $this->atomize('migration2', [$schema]);
        $this->migrator->run();
    }


    public function testCreateAndThenUpdateEnumDefault()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->enum('value', ['a', 'b'])->defaultValue('a');
        $schema->index(['value']);
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertSame(['a', 'b'], $this->schema('sample')->column('value')->getEnumValues());

        $schema = $this->schema('sample');
        $schema->enum('value', ['a', 'b', 'c']);
        $schema->index(['value'])->unique(true);
        $this->atomize('migration2', [$schema]);

        $this->migrator->run();
        $this->assertSame(
            ['a', 'b', 'c'],
            $this->schema('sample')->column('value')->getEnumValues()
        );

        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertSame(['a', 'b'], $this->schema('sample')->column('value')->getEnumValues());

        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }

    public function testChangeColumnScale()
    {
        //Create thought migration
        $this->migrator->configure();

        $schema = $this->schema('sample');
        $schema->primary('id');
        $schema->decimal('value', 2, 1);
        $this->atomize('migration1', [$schema]);

        $this->migrator->run();
        $this->assertSame(2, $this->schema('sample')->column('value')->getPrecision());
        $this->assertSame(1, $this->schema('sample')->column('value')->getScale());

        $schema = $this->schema('sample');
        $schema->decimal('value', 3, 2);
        $this->atomize('migration2', [$schema]);

        $this->migrator->run();

        $this->assertSame(3, $this->schema('sample')->column('value')->getPrecision());
        $this->assertSame(2, $this->schema('sample')->column('value')->getScale());

        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertSame(2, $this->schema('sample')->column('value')->getPrecision());
        $this->assertSame(1, $this->schema('sample')->column('value')->getScale());

        $this->assertTrue($this->db->hasTable('sample'));

        $this->migrator->rollback();
        $this->assertFalse($this->db->hasTable('sample'));
    }
}