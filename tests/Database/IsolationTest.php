<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Driver\AbstractHandler;
use Spiral\Database\Schema\AbstractTable;

abstract class IsolationTest extends BaseTest
{
    public function tearDown()
    {
        $this->dropDatabase($this->db());
    }

    public function schema(string $prefix, string $table): AbstractTable
    {
        return $this->db('default', $prefix)->table($table)->getSchema();
    }

    public function testGetPrefix()
    {
        $schema = $this->schema('prefix_', 'table');
        $this->assertFalse($schema->exists());

        $this->assertSame('prefix_', $schema->getPrefix());
        $this->assertSame('prefix_table', $schema->getName());

        $schema->primary('id');
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertTrue($this->schema('prefix_', 'table')->exists());
    }

    public function testChangeNameBeforeSave()
    {
        $schema = $this->schema('prefix_', 'table');
        $this->assertFalse($schema->exists());

        $this->assertSame('prefix_', $schema->getPrefix());
        $this->assertSame('prefix_table', $schema->getName());

        $schema->setName('new_name');
        $this->assertSame('prefix_new_name', $schema->getName());

        $schema->primary('id');
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertTrue($this->schema('prefix_new_', 'name')->exists());
        $this->assertTrue($this->schema('prefix_', 'new_name')->exists());
    }

    public function testRename()
    {
        $schema = $this->schema('prefix_', 'table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertTrue($this->schema('prefix_', 'table')->exists());

        $schema->setName('abc');
        $schema->save(AbstractHandler::DO_ALL);

        $this->assertFalse($this->schema('prefix_', 'table')->exists());
        $this->assertTrue($this->schema('prefix_', 'abc')->exists());
    }

    public function testCreateAndMakeReferenceInSelfScope()
    {
        $schema = $this->schema('prefix_', 'a');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save(AbstractHandler::DO_ALL);

        $schema = $this->schema('prefix_', 'b');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('to_a');
        $schema->foreign('to_a')->references('a', 'id');

        $this->assertSame('prefix_b', $schema->column('id')->getTable());
        $this->assertSame('prefix_a',   $schema->foreign('to_a')->getForeignTable());

        $schema->save(AbstractHandler::DO_ALL);

        $this->assertTrue($this->schema('prefix_', 'a')->exists());
        $this->assertTrue($this->schema('prefix_', 'b')->exists());

        $foreign = $this->schema('prefix_', 'b')->foreign('to_a');

        $this->assertSame('prefix_a', $foreign->getForeignTable());
    }
}