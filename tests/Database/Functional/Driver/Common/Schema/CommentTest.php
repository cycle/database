<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

// phpcs:ignore
use Cycle\Database\ColumnInterface;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Utils\DontGenerateAttribute;

#[DontGenerateAttribute]
abstract class CommentTest extends BaseTest
{
    public function testAddComment(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $column2 = $schema->column('target');
        $this->assertTrue($column2->compare($column));
        self::assertSame('foo', $column2->getComment());
    }

    public function testChangeComment(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $column2 = $schema->column('target');
        $column2->comment('bar');

        $schema->save();

        $this->assertTrue($schema->column('target')->compare($column2));
        self::assertSame('bar', $schema->column('target')->getComment());
    }

    public function testChangeCommentToEmpty(): void
    {
        $schema = $this->schema('table');
        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $column2 = $schema->column('target');
        self::assertSame('foo', $column2->getComment());

        $column2->comment('');

        $schema->save();

        $schema = $this->schema('table');
        $column3 = $schema->column('target');
        self::assertSame('', $column3->getComment());
    }

    public function testCommentWithAutoIncrement(): void
    {
        $schema = $this->schema('table');
        $column = $schema->primary('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $column2 = $schema->column('target');
        $this->assertTrue($column2->compare($column));
        self::assertSame('foo', $column2->getComment());
    }

    public function testSQLInjection(): void
    {
        $schema = $this->schema('table');
        $column = $schema->string('target');
        $column->comment('f"o\'o`');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $column2 = $schema->column('target');
        $this->assertTrue($column2->compare($column));
        self::assertSame('f"o\'o`', $column2->getComment());
    }

    public function testSQLInjectionIntegers(): void
    {
        $schema = $this->schema('table');
        $column = $schema->primary('target');
        $column->comment('f"o\'o`');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $column2 = $schema->column('target');
        $this->assertTrue($column2->compare($column));
        self::assertSame('f"o\'o`', $column2->getComment());
    }
}
