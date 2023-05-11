<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ForeignKeysTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ForeignKeysTest extends CommonClass
{
    public const DRIVER = 'sqlserver';

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
}
