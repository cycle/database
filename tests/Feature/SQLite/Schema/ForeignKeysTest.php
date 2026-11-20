<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLite\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Feature\Common\Schema\ForeignKeysTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Testo\Assert;
use Testo\Test;

final class ForeignKeysTest extends CommonClass
{
    #[Test]
    public function testCreateWithoutIndex(): void
    {
        $schema = $this->schema('schema');
        Assert::false($schema->exists());
        Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'], false)->references('external', ['id']);

        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);
        Assert::true($this->schema('schema')->hasForeignKey(['external_id']));
        Assert::false($this->schema('schema')->hasIndex(['external_id']));
    }

    #[Test]
    public function testDisableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');
        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();
        Assert::same((int) $result['foreign_keys'], 1);

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();

        Assert::same((int) $result['foreign_keys'], 0);
    }

    #[Test]
    public function testEnableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();
        Assert::same((int) $result['foreign_keys'], 0);

        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();

        Assert::same((int) $result['foreign_keys'], 1);
    }
}
