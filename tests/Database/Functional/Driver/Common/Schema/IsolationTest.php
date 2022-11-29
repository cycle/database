<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class IsolationTest extends BaseTest
{
    public function testGetPrefix(): void
    {
        $schema = $this->schema('table', 'prefix_');
        $this->assertFalse($schema->exists());

        $this->assertSame('prefix_', $schema->getPrefix());
        $this->assertSame('prefix_table', $schema->getName());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('table', 'prefix_')->exists());
    }

    public function testChangeNameBeforeSave(): void
    {
        $schema = $this->schema('table', 'prefix_');
        $this->assertFalse($schema->exists());

        $this->assertSame('prefix_', $schema->getPrefix());
        $this->assertSame('prefix_table', $schema->getName());

        $schema->setName('new_name');
        $this->assertSame('prefix_new_name', $schema->getName());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('name', 'prefix_new_')->exists());
        $this->assertTrue($this->schema('new_name', 'prefix_')->exists());
    }

    public function testRename(): void
    {
        $schema = $this->schema('table', 'prefix_');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('table', 'prefix_')->exists());

        $schema->setName('abc');
        $schema->save(Handler::DO_ALL);

        $this->assertFalse($this->schema('table', 'prefix_')->exists());
        $this->assertTrue($this->schema('abc', 'prefix_')->exists());
    }

    public function testCreateAndMakeReferenceInSelfScope(): void
    {
        $schema = $this->schema('a', 'prefix_');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $schema = $this->schema('b', 'prefix_');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('to_a');
        $schema->foreignKey(['to_a'])->references('a', ['id']);

        $this->assertSame('prefix_b', $schema->column('id')->getTable());
        $this->assertSame('prefix_a', $schema->foreignKey(['to_a'])->getForeignTable());

        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('a', 'prefix_')->exists());
        $this->assertTrue($this->schema('b', 'prefix_')->exists());

        $foreign = $this->schema('b', 'prefix_')->foreignKey(['to_a']);

        $this->assertSame('prefix_a', $foreign->getForeignTable());
    }
}
