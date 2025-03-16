<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\MySQL\Schema\MySQLColumn;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-mysql
 */
class CommentTest extends BaseTest
{
    public const DRIVER = 'mysql';

    public function testAddComment(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        /** @var MySqlColumn $column */
        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertTrue($schema->column('target')->compare($column));
    }

    public function testChangeComment(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        /** @var MySqlColumn $column */
        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        /** @var MySqlColumn $column */
        $column = $schema->string('target');
        $column->comment('bar');

        $schema->save();

        $this->assertTrue($schema->column('target')->compare($column));
    }
}
