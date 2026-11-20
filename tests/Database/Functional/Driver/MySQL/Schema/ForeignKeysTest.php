<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\ForeignKeysTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class ForeignKeysTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testDisableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();
        \Testo\Assert::same((int) $result['@@foreign_key_checks'], 1);

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();

        \Testo\Assert::same((int) $result['@@foreign_key_checks'], 0);
    }

    #[\Testo\Test]
    public function testEnableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();
        \Testo\Assert::same((int) $result['@@foreign_key_checks'], 0);

        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();

        \Testo\Assert::same((int) $result['@@foreign_key_checks'], 1);
    }
}
