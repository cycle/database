<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class IsolationTest extends BaseTest
{
    #[\Testo\Test]
    public function testGetPrefix(): void
    {
        $schema = $this->schema('table', 'prefix_');
        \Testo\Assert::false($schema->exists());

        \Testo\Assert::same($schema->getPrefix(), 'prefix_');
        \Testo\Assert::same($schema->getName(), 'prefix_table');

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        \Testo\Assert::true($this->schema('table', 'prefix_')->exists());
    }

    #[\Testo\Test]
    public function testChangeNameBeforeSave(): void
    {
        $schema = $this->schema('table', 'prefix_');
        \Testo\Assert::false($schema->exists());

        \Testo\Assert::same($schema->getPrefix(), 'prefix_');
        \Testo\Assert::same($schema->getName(), 'prefix_table');

        $schema->setName('new_name');
        \Testo\Assert::same($schema->getName(), 'prefix_new_name');

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        \Testo\Assert::true($this->schema('name', 'prefix_new_')->exists());
        \Testo\Assert::true($this->schema('new_name', 'prefix_')->exists());
    }

    #[\Testo\Test]
    public function testRename(): void
    {
        $schema = $this->schema('table', 'prefix_');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        \Testo\Assert::true($this->schema('table', 'prefix_')->exists());

        $schema->setName('abc');
        $schema->save(Handler::DO_ALL);

        \Testo\Assert::false($this->schema('table', 'prefix_')->exists());
        \Testo\Assert::true($this->schema('abc', 'prefix_')->exists());
    }

    #[\Testo\Test]
    public function testCreateAndMakeReferenceInSelfScope(): void
    {
        $schema = $this->schema('a', 'prefix_');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $schema = $this->schema('b', 'prefix_');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('to_a');
        $schema->foreignKey(['to_a'])->references('a', ['id']);

        \Testo\Assert::same($schema->column('id')->getTable(), 'prefix_b');
        \Testo\Assert::same($schema->foreignKey(['to_a'])->getForeignTable(), 'prefix_a');

        $schema->save(Handler::DO_ALL);

        \Testo\Assert::true($this->schema('a', 'prefix_')->exists());
        \Testo\Assert::true($this->schema('b', 'prefix_')->exists());

        $foreign = $this->schema('b', 'prefix_')->foreignKey(['to_a']);

        \Testo\Assert::same($foreign->getForeignTable(), 'prefix_a');
    }
}
