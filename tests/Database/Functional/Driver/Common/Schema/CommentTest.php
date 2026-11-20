<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;
use Cycle\Database\Tests\Utils\DontGenerateAttribute;

#[DontGenerateAttribute]
abstract class CommentTest extends BaseTest
{
    #[\Testo\Test]
    public function testAddComment(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $column2 = $schema->column('target');
        \Testo\Assert::true($column2->compare($column));
        \Testo\Assert::same($column2->getComment(), 'foo');
    }

    #[\Testo\Test]
    public function testChangeComment(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $column2 = $schema->column('target');
        $column2->comment('bar');

        $schema->save();

        \Testo\Assert::true($schema->column('target')->compare($column2));
        \Testo\Assert::same($schema->column('target')->getComment(), 'bar');
    }

    #[\Testo\Test]
    public function testChangeCommentToEmpty(): void
    {
        $schema = $this->schema('table');
        $column = $schema->string('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $column2 = $schema->column('target');
        \Testo\Assert::same($column2->getComment(), 'foo');

        $column2->comment('');

        $schema->save();

        $schema = $this->schema('table');
        $column3 = $schema->column('target');
        \Testo\Assert::same($column3->getComment(), '');
    }

    #[\Testo\Test]
    public function testCommentWithAutoIncrement(): void
    {
        $schema = $this->schema('table');
        $column = $schema->primary('target');
        $column->comment('foo');

        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $column2 = $schema->column('target');
        \Testo\Assert::true($column2->compare($column));
        \Testo\Assert::same($column2->getComment(), 'foo');
    }

    #[\Testo\Test]
    public function testSQLInjection(): void
    {
        $schema = $this->schema('table');
        $column = $schema->string('target');
        $column->comment('f"o\'o`');

        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $column2 = $schema->column('target');
        \Testo\Assert::true($column2->compare($column));
        \Testo\Assert::same($column2->getComment(), 'f"o\'o`');
    }

    #[\Testo\Test]
    public function testSQLInjectionIntegers(): void
    {
        $schema = $this->schema('table');
        $column = $schema->primary('target');
        $column->comment('f"o\'o`');

        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        $column2 = $schema->column('target');
        \Testo\Assert::true($column2->compare($column));
        \Testo\Assert::same($column2->getComment(), 'f"o\'o`');
    }
}
