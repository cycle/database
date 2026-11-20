<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\ForeignKeysTest as CommonClass;
use Testo\Assert;
use Testo\Test;

final class ForeignKeysTest extends CommonClass
{
    #[Test]
    public function testDisableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();
        Assert::same((int) $result['@@foreign_key_checks'], 1);

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();

        Assert::same((int) $result['@@foreign_key_checks'], 0);
    }

    #[Test]
    public function testEnableForeignKeyConstraints(): void
    {
        $schema = $this->schema('schema');

        $schema->getDriver()->getSchemaHandler()->disableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();
        Assert::same((int) $result['@@foreign_key_checks'], 0);

        $schema->getDriver()->getSchemaHandler()->enableForeignKeyConstraints();
        $result = $schema->getDriver()->query('SELECT @@foreign_key_checks;')->fetch();

        Assert::same((int) $result['@@foreign_key_checks'], 1);
    }
}
