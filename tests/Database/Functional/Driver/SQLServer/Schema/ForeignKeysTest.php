<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ForeignKeysTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class ForeignKeysTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

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
        $this->sampleSchema('external');

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('external', ['id']);
        $schema->save(Handler::DO_ALL);

        $result = $schema->getDriver()->query('select * from sys.foreign_keys')->fetch();
        \Testo\Assert::same((int) $result['is_disabled'], 0);

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('select * from sys.foreign_keys')->fetch();
        \Testo\Assert::same((int) $result['is_disabled'], 1);
    }

    #[\Testo\Test]
    public function testEnableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');
        $this->sampleSchema('external');

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('external', ['id']);
        $schema->save(Handler::DO_ALL);

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('select * from sys.foreign_keys')->fetch();
        \Testo\Assert::same((int) $result['is_disabled'], 1);
        $schema->save(Handler::DO_ALL);

        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('select * from sys.foreign_keys')->fetch();
        \Testo\Assert::same((int) $result['is_disabled'], 0);
    }
}
