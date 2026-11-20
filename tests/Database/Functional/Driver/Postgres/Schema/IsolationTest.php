<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\IsolationTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class IsolationTest extends CommonClass
{
    public const DRIVER = 'postgres';

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

        \Testo\Assert::same($schema->column('id')->getTable(), 'public.prefix_b');
        \Testo\Assert::same($schema->foreignKey(['to_a'])->getForeignTable(), 'prefix_a');

        $schema->save(Handler::DO_ALL);

        \Testo\Assert::true($this->schema('a', 'prefix_')->exists());
        \Testo\Assert::true($this->schema('b', 'prefix_')->exists());

        $foreign = $this->schema('b', 'prefix_')->foreignKey(['to_a']);

        \Testo\Assert::same($foreign->getForeignTable(), 'prefix_a');
    }
}
