<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ReflectorTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ReflectorTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testCreateTableAndLinkAndChangeLink(): void
    {
        $schemaA = $this->schema('a');
        $this->assertFalse($schemaA->exists());

        $schemaB = $this->schema('b');
        $this->assertFalse($schemaB->exists());

        $schemaA->primary('id');
        $schemaA->integer('value');

        $schemaB->integer('id')->primary();
        $schemaB->string('value');

        $schemaA->integer('b_id');
        $schemaA->foreignKey(['b_id'])->references('b', ['id']);

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);

        $schemaA->integer('b_id')->unsigned(true);
        $schemaB->integer('id')->unsigned(true);

        $this->saveTables([$schemaA, $schemaB]);

        $this->assertSameAsInDB($schemaA);
        $this->assertSameAsInDB($schemaB);
    }
}
