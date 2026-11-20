<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class CharsetCollationTest extends BaseTest
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testStringColumnWithCharsetAndCollation(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $column = $schema->string('email', size: 255);
        $column->charset('ascii');
        $column->collation('ascii_bin');
        $column->nullable(false);
        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $emailColumn = $schema->column('email');
        \assert($emailColumn instanceof MySQLColumn);

        \Testo\Assert::same($emailColumn->getCharset(), 'ascii');
        \Testo\Assert::same($emailColumn->getCollation(), 'ascii_bin');
    }

    #[\Testo\Test]
    public function testStringColumnCharsetCollationPersistsAfterReload(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $column = $schema->string('name', size: 100);
        $column->charset('utf8mb4');
        $column->collation('utf8mb4_unicode_ci');
        $schema->save();

        $schema = $this->schema('table');

        $nameColumn = $schema->column('name');
        \assert($nameColumn instanceof MySQLColumn);

        \Testo\Assert::same($nameColumn->getCharset(), 'utf8mb4');
        \Testo\Assert::same($nameColumn->getCollation(), 'utf8mb4_unicode_ci');
        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeCollation(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $column = $schema->string('title', size: 255);
        $column->charset('ascii');
        $column->collation('ascii_general_ci');
        $schema->save();

        $schema = $this->schema('table');
        $titleColumn = $schema->column('title');
        \assert($titleColumn instanceof MySQLColumn);
        \Testo\Assert::same($titleColumn->getCollation(), 'ascii_general_ci');

        // Change collation
        $titleColumn->collation('ascii_bin');
        $schema->save();

        $schema = $this->schema('table');
        $titleColumn = $schema->column('title');
        \assert($titleColumn instanceof MySQLColumn);
        \Testo\Assert::same($titleColumn->getCollation(), 'ascii_bin');
    }

    #[\Testo\Test]
    public function testTextColumnWithCharsetAndCollation(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $column = $schema->text('content');
        $column->charset('utf8mb4');
        $column->collation('utf8mb4_bin');
        $schema->save();

        $schema = $this->schema('table');

        $contentColumn = $schema->column('content');
        \assert($contentColumn instanceof MySQLColumn);

        \Testo\Assert::same($contentColumn->getCharset(), 'utf8mb4');
        \Testo\Assert::same($contentColumn->getCollation(), 'utf8mb4_bin');
    }

    #[\Testo\Test]
    public function testColumnWithoutExplicitCharsetInheritsDefault(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->string('name', size: 100);
        $schema->save();

        $schema = $this->schema('table');
        $this->assertSameAsInDB($schema);

        $nameColumn = $schema->column('name');
        \assert($nameColumn instanceof MySQLColumn);

        // Column should have inherited charset/collation from table/database defaults
        \Testo\Assert::false(empty($nameColumn->getCharset()));
        \Testo\Assert::false(empty($nameColumn->getCollation()));
    }

    #[\Testo\Test]
    public function testCompareColumnsWithSameCharset(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $column = $schema->string('email', size: 255);
        $column->charset('ascii');
        $column->collation('ascii_bin');
        $column->nullable(false);
        $schema->save();

        $schema = $this->schema('table');
        $dbColumn = $schema->column('email');

        \Testo\Assert::true($column->compare($dbColumn));
    }
}
