<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ForeignKeysTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlite')]
class ForeignKeysTest extends CommonClass
{
    public const DRIVER = 'sqlite';

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

    #[\Testo\Test]
    public function testDisableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');
        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();
        \Testo\Assert::same((int) $result['foreign_keys'], 1);

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();

        \Testo\Assert::same((int) $result['foreign_keys'], 0);
    }

    #[\Testo\Test]
    public function testEnableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();
        \Testo\Assert::same((int) $result['foreign_keys'], 0);

        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();

        \Testo\Assert::same((int) $result['foreign_keys'], 1);
    }
}
