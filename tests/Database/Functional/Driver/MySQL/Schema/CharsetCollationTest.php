<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-mysql
 */
class CharsetCollationTest extends BaseTest
{
    public const DRIVER = 'mysql';

    public function testStringColumnWithCharsetAndCollation(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $column = $schema->string('email', size: 255);
        $column->charset('ascii');
        $column->collation('ascii_bin');
        $column->nullable(false);
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $emailColumn = $schema->column('email');
        \assert($emailColumn instanceof MySQLColumn);

        $this->assertSame('ascii', $emailColumn->getCharset());
        $this->assertSame('ascii_bin', $emailColumn->getCollation());
    }

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

        $this->assertSame('utf8mb4', $nameColumn->getCharset());
        $this->assertSame('utf8mb4_unicode_ci', $nameColumn->getCollation());
        $this->assertSameAsInDB($schema);
    }

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
        $this->assertSame('ascii_general_ci', $titleColumn->getCollation());

        // Change collation
        $titleColumn->collation('ascii_bin');
        $schema->save();

        $schema = $this->schema('table');
        $titleColumn = $schema->column('title');
        \assert($titleColumn instanceof MySQLColumn);
        $this->assertSame('ascii_bin', $titleColumn->getCollation());
    }

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

        $this->assertSame('utf8mb4', $contentColumn->getCharset());
        $this->assertSame('utf8mb4_bin', $contentColumn->getCollation());
    }

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
        $this->assertNotEmpty($nameColumn->getCharset());
        $this->assertNotEmpty($nameColumn->getCollation());
    }

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

        $this->assertTrue($column->compare($dbColumn));
    }
}
