<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Driver\Handler;
use Spiral\Database\Schema\AbstractTable;

abstract class IsolationTest extends BaseTest
{
    public function tearDown(): void
    {
        $this->dropDatabase($this->db());
    }

    public function schema(string $prefix, string $table): AbstractTable
    {
        return $this->db('default', $prefix)->table($table)->getSchema();
    }

    public function testGetPrefix(): void
    {
        $schema = $this->schema('prefix_', 'table');
        $this->assertFalse($schema->exists());

        $this->assertSame('prefix_', $schema->getPrefix());
        $this->assertSame('prefix_table', $schema->getName());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('prefix_', 'table')->exists());
    }

    public function testChangeNameBeforeSave(): void
    {
        $schema = $this->schema('prefix_', 'table');
        $this->assertFalse($schema->exists());

        $this->assertSame('prefix_', $schema->getPrefix());
        $this->assertSame('prefix_table', $schema->getName());

        $schema->setName('new_name');
        $this->assertSame('prefix_new_name', $schema->getName());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('prefix_new_', 'name')->exists());
        $this->assertTrue($this->schema('prefix_', 'new_name')->exists());
    }

    public function testRename(): void
    {
        $schema = $this->schema('prefix_', 'table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('prefix_', 'table')->exists());

        $schema->setName('abc');
        $schema->save(Handler::DO_ALL);

        $this->assertFalse($this->schema('prefix_', 'table')->exists());
        $this->assertTrue($this->schema('prefix_', 'abc')->exists());
    }

    public function testCreateAndMakeReferenceInSelfScope(): void
    {
        $schema = $this->schema('prefix_', 'a');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $schema = $this->schema('prefix_', 'b');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('to_a');
        $schema->foreignKey(['to_a'])->references('a', ['id']);

        $this->assertSame('prefix_b', $schema->column('id')->getTable());
        $this->assertSame('prefix_a', $schema->foreignKey(['to_a'])->getForeignTable());

        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('prefix_', 'a')->exists());
        $this->assertTrue($this->schema('prefix_', 'b')->exists());

        $foreign = $this->schema('prefix_', 'b')->foreignKey(['to_a']);

        $this->assertSame('prefix_a', $foreign->getForeignTable());
    }
}
