<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

final class AutoIncrementColumnTest extends DatabaseTestCase
{
    public static function primaryTypesProvider(): iterable
    {
        yield 'primary' => ['primary', 'primary'];
        yield 'smallPrimary' => ['smallPrimary', 'smallPrimary'];
        yield 'bigPrimary' => ['bigPrimary', 'bigPrimary'];
    }

    #[Test]
    public function testAutoIncrementColumnIsNotAddedToPrimaryKey(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true);
        $schema->bigInteger('big_number', autoIncrement: true);
        $schema->setPrimaryKeys(['id']);

        Assert::same($schema->getPrimaryKeys(), ['id']);
        Assert::same($schema->column('number')->getAbstractType(), 'integer');
        Assert::same($schema->column('big_number')->getAbstractType(), 'bigInteger');
    }

    #[Test]
    public function testCreateTableWithAutoIncrementColumnOutsidePrimaryKey(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->string('title')->nullable(true);
        $schema->setPrimaryKeys(['id']);
        $schema->index(['number'])->unique();
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $saved = $this->schema('auto_increment');
        Assert::same($saved->getPrimaryKeys(), ['id']);
        Assert::same($saved->column('number')->getAbstractType(), 'integer');
        Assert::true($saved->hasIndex(['number']));

        $table = $this->database->table('auto_increment');
        $table->insertOne(['id' => 'a', 'title' => 'first']);
        $table->insertOne(['id' => 'b', 'title' => 'second']);
        Assert::same($table->select('id', 'number')->orderBy('id')->fetchAll(), [['id' => 'a', 'number' => 1], ['id' => 'b', 'number' => 2]]);
    }

    #[Test]
    public function testCreateTableWithNonUniqueIndexOnAutoIncrementColumn(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->bigInteger('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id']);
        $schema->index(['number']);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->schema('auto_increment')->getPrimaryKeys(), ['id']);
    }

    #[Test]
    public function testCreateTableWithAutoIncrementColumnNotFirstInPrimaryKey(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id', 'number']);
        $schema->index(['number'])->unique();
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->schema('auto_increment')->getPrimaryKeys(), ['id', 'number']);
    }

    #[Test]
    public function testAutoIncrementColumnWithoutKeyThrowsException(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id']);

        Expect::exception(SchemaException::class)->withMessageContaining('`number`');

        $schema->save();
    }

    #[Test]
    public function testExistingTableIsReflectedWithItsPrimaryKey(): void
    {
        $this->database->execute(
            'CREATE TABLE `auto_increment` (
                `id` varchar(36) NOT NULL,
                `number` int NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id`),
                UNIQUE KEY `auto_increment_number` (`number`)
            )',
        );

        $schema = $this->schema('auto_increment');
        Assert::same($schema->getPrimaryKeys(), ['id']);
        Assert::same($schema->column('number')->getAbstractType(), 'integer');

        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->index(['number'])->unique()->setName('auto_increment_number');
        $schema->setPrimaryKeys(['id']);

        Assert::false($schema->getComparator()->hasChanges());
    }

    #[Test]
    public function testPrimaryKeyListedExplicitlyIsReflectedWithoutChanges(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['number', 'id']);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->schema('auto_increment')->getPrimaryKeys(), ['number', 'id']);

        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['number', 'id']);

        Assert::false($schema->getComparator()->hasChanges());
    }

    #[Test]
    #[DataProvider('primaryTypesProvider')]
    public function testPrimaryTypesStayInPrimaryKey(string $type, string $abstractType): void
    {
        $schema = $this->schema('auto_increment');
        $schema->$type('id');
        $schema->string('title');
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        $saved = $this->schema('auto_increment');
        Assert::same($saved->getPrimaryKeys(), ['id']);
        Assert::same($saved->column('id')->getAbstractType(), $abstractType);
    }
}
