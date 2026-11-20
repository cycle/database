<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[ExceptDriver(Driver::Postgres)]
class IsolationTest extends DatabaseTestCase
{
    #[Test]
    public function testGetPrefix(): void
    {
        $schema = $this->schema('table', 'prefix_');
        Assert::false($schema->exists());

        Assert::same($schema->getPrefix(), 'prefix_');
        Assert::same($schema->getName(), 'prefix_table');

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        Assert::true($this->schema('table', 'prefix_')->exists());
    }

    #[Test]
    public function testChangeNameBeforeSave(): void
    {
        $schema = $this->schema('table', 'prefix_');
        Assert::false($schema->exists());

        Assert::same($schema->getPrefix(), 'prefix_');
        Assert::same($schema->getName(), 'prefix_table');

        $schema->setName('new_name');
        Assert::same($schema->getName(), 'prefix_new_name');

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        Assert::true($this->schema('name', 'prefix_new_')->exists());
        Assert::true($this->schema('new_name', 'prefix_')->exists());
    }

    #[Test]
    public function testRename(): void
    {
        $schema = $this->schema('table', 'prefix_');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        Assert::true($this->schema('table', 'prefix_')->exists());

        $schema->setName('abc');
        $schema->save(Handler::DO_ALL);

        Assert::false($this->schema('table', 'prefix_')->exists());
        Assert::true($this->schema('abc', 'prefix_')->exists());
    }

    #[Test]
    public function testCreateAndMakeReferenceInSelfScope(): void
    {
        $schema = $this->schema('a', 'prefix_');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $schema = $this->schema('b', 'prefix_');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->integer('to_a');
        $schema->foreignKey(['to_a'])->references('a', ['id']);

        Assert::same($schema->column('id')->getTable(), 'prefix_b');
        Assert::same($schema->foreignKey(['to_a'])->getForeignTable(), 'prefix_a');

        $schema->save(Handler::DO_ALL);

        Assert::true($this->schema('a', 'prefix_')->exists());
        Assert::true($this->schema('b', 'prefix_')->exists());

        $foreign = $this->schema('b', 'prefix_')->foreignKey(['to_a']);

        Assert::same($foreign->getForeignTable(), 'prefix_a');
    }
}
