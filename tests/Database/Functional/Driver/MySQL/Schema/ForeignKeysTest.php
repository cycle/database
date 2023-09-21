<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ForeignKeysTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class ForeignKeysTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testDisableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();
        $this->assertSame(1, (int) $result['@@foreign_key_checks']);

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();

        $this->assertSame(0, (int) $result['@@foreign_key_checks']);
    }

    public function testEnableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();
        $this->assertSame(0, (int) $result['@@foreign_key_checks']);

        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();

        $this->assertSame(1, (int) $result['@@foreign_key_checks']);
    }
}
