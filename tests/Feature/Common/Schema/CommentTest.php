<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[ExceptDriver(Driver::SQLite, Driver::SQLServer)]
final class CommentTest extends DatabaseTestCase
{
    #[Test]
    public function testAddComment(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $column2 = $schema->column('target');
        Assert::true($column2->compare($column));
        Assert::same($column2->getComment(), 'foo');
    }

    #[Test]
    public function testChangeComment(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $column2 = $schema->column('target');
        $column2->comment('bar');

        $schema->save();

        Assert::true($schema->column('target')->compare($column2));
        Assert::same($schema->column('target')->getComment(), 'bar');
    }

    #[Test]
    public function testChangeCommentToEmpty(): void
    {
        $schema = $this->schema('table');
        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $column2 = $schema->column('target');
        Assert::same($column2->getComment(), 'foo');

        $column2->comment('');

        $schema->save();

        $schema = $this->schema('table');
        $column3 = $schema->column('target');
        Assert::same($column3->getComment(), '');
    }

    #[Test]
    public function testCommentWithAutoIncrement(): void
    {
        $schema = $this->schema('table');
        $column = $schema->primary('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $column2 = $schema->column('target');
        Assert::true($column2->compare($column));
        Assert::same($column2->getComment(), 'foo');
    }

    #[Test]
    public function testSQLInjection(): void
    {
        $schema = $this->schema('table');
        $column = $schema->string('target');
        $column->comment('f"o\'o`');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $column2 = $schema->column('target');
        Assert::true($column2->compare($column));
        Assert::same($column2->getComment(), 'f"o\'o`');
    }

    #[Test]
    public function testSQLInjectionIntegers(): void
    {
        $schema = $this->schema('table');
        $column = $schema->primary('target');
        $column->comment('f"o\'o`');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());

        $column2 = $schema->column('target');
        Assert::true($column2->compare($column));
        Assert::same($column2->getComment(), 'f"o\'o`');
    }
}
