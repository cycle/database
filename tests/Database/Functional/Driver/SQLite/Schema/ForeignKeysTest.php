<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ForeignKeysTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class ForeignKeysTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    public function testCreateWithoutIndex(): void
    {
        $schema = $this->schema('schema');
        $this->assertFalse($schema->exists());
        $this->assertTrue($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'], false)->references('external', ['id']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        $this->assertTrue($this->schema('schema')->hasForeignKey(['external_id']));
        $this->assertFalse($this->schema('schema')->hasIndex(['external_id']));
    }

    public function testDisableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');
        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();
        $this->assertSame(1, (int) $result['foreign_keys']);

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();

        $this->assertSame(0, (int) $result['foreign_keys']);
    }

    public function testEnableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();
        $this->assertSame(0, (int) $result['foreign_keys']);

        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('PRAGMA foreign_keys')->fetch();

        $this->assertSame(1, (int) $result['foreign_keys']);
    }
}
