<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class CharsetCollationTest extends DatabaseTestCase
{
    #[Test]
    public function testStringColumnWithCharsetAndCollation(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->primary('id');
        $column = $schema->string('email', size: 255);
        $column->charset('ascii');
        $column->collation('ascii_bin');
        $column->nullable(false);
        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $emailColumn = $schema->column('email');
        \assert($emailColumn instanceof MySQLColumn);

        Assert::same($emailColumn->getCharset(), 'ascii');
        Assert::same($emailColumn->getCollation(), 'ascii_bin');
    }

    #[Test]
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

        Assert::same($nameColumn->getCharset(), 'utf8mb4');
        Assert::same($nameColumn->getCollation(), 'utf8mb4_unicode_ci');
        SchemaAssert::sameAsInDb($schema);
    }

    #[Test]
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
        Assert::same($titleColumn->getCollation(), 'ascii_general_ci');

        // Change collation
        $titleColumn->collation('ascii_bin');
        $schema->save();

        $schema = $this->schema('table');
        $titleColumn = $schema->column('title');
        \assert($titleColumn instanceof MySQLColumn);
        Assert::same($titleColumn->getCollation(), 'ascii_bin');
    }

    #[Test]
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

        Assert::same($contentColumn->getCharset(), 'utf8mb4');
        Assert::same($contentColumn->getCollation(), 'utf8mb4_bin');
    }

    #[Test]
    public function testColumnWithoutExplicitCharsetInheritsDefault(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->string('name', size: 100);
        $schema->save();

        $schema = $this->schema('table');
        SchemaAssert::sameAsInDb($schema);

        $nameColumn = $schema->column('name');
        \assert($nameColumn instanceof MySQLColumn);

        // Column should have inherited charset/collation from table/database defaults
        Assert::false(empty($nameColumn->getCharset()));
        Assert::false(empty($nameColumn->getCollation()));
    }

    #[Test]
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

        Assert::true($column->compare($dbColumn));
    }
}
