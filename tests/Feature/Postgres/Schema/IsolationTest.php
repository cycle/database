<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Feature\Common\Schema\IsolationTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class IsolationTest extends CommonClass
{
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

        Assert::same($schema->column('id')->getTable(), 'public.prefix_b');
        Assert::same($schema->foreignKey(['to_a'])->getForeignTable(), 'prefix_a');

        $schema->save(Handler::DO_ALL);

        Assert::true($this->schema('a', 'prefix_')->exists());
        Assert::true($this->schema('b', 'prefix_')->exists());

        $foreign = $this->schema('b', 'prefix_')->foreignKey(['to_a']);

        Assert::same($foreign->getForeignTable(), 'prefix_a');
    }
}
