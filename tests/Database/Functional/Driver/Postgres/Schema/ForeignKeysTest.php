<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ForeignKeysTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class ForeignKeysTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testCreateWithoutIndex(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'], false)->references('external', ['id']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));
        \Testo\Assert::false($this->schema('schema')->hasIndex(['external_id']));
    }
}
