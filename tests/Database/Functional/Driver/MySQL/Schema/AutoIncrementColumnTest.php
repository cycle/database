<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
final class AutoIncrementColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';

    public static function primaryTypesProvider(): iterable
    {
        yield 'primary' => ['primary', 'primary'];
        yield 'smallPrimary' => ['smallPrimary', 'smallPrimary'];
        yield 'bigPrimary' => ['bigPrimary', 'bigPrimary'];
    }

    #[\Testo\Test]
    public function testAutoIncrementColumnIsNotAddedToPrimaryKey(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true);
        $schema->bigInteger('big_number', autoIncrement: true);
        $schema->setPrimaryKeys(['id']);

        \Testo\Assert::same($schema->getPrimaryKeys(), ['id']);
        \Testo\Assert::same($schema->column('number')->getAbstractType(), 'integer');
        \Testo\Assert::same($schema->column('big_number')->getAbstractType(), 'bigInteger');
    }

    #[\Testo\Test]
    public function testCreateTableWithAutoIncrementColumnOutsidePrimaryKey(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->string('title')->nullable(true);
        $schema->setPrimaryKeys(['id']);
        $schema->index(['number'])->unique();
        $schema->save();

        $this->assertSameAsInDB($schema);

        $saved = $this->schema('auto_increment');
        \Testo\Assert::same($saved->getPrimaryKeys(), ['id']);
        \Testo\Assert::same($saved->column('number')->getAbstractType(), 'integer');
        \Testo\Assert::true($saved->hasIndex(['number']));

        $table = $this->database->table('auto_increment');
        $table->insertOne(['id' => 'a', 'title' => 'first']);
        $table->insertOne(['id' => 'b', 'title' => 'second']);
        \Testo\Assert::same($table->select('id', 'number')->orderBy('id')->fetchAll(), [['id' => 'a', 'number' => 1], ['id' => 'b', 'number' => 2]]);
    }

    #[\Testo\Test]
    public function testCreateTableWithNonUniqueIndexOnAutoIncrementColumn(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->bigInteger('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id']);
        $schema->index(['number']);
        $schema->save();

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->schema('auto_increment')->getPrimaryKeys(), ['id']);
    }

    #[\Testo\Test]
    public function testCreateTableWithAutoIncrementColumnNotFirstInPrimaryKey(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id', 'number']);
        $schema->index(['number'])->unique();
        $schema->save();

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->schema('auto_increment')->getPrimaryKeys(), ['id', 'number']);
    }

    #[\Testo\Test]
    public function testAutoIncrementColumnWithoutKeyThrowsException(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id']);

        \Testo\Expect::exception(SchemaException::class)->withMessageContaining('`number`');

        $schema->save();
    }

    #[\Testo\Test]
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
        \Testo\Assert::same($schema->getPrimaryKeys(), ['id']);
        \Testo\Assert::same($schema->column('number')->getAbstractType(), 'integer');

        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->index(['number'])->unique()->setName('auto_increment_number');
        $schema->setPrimaryKeys(['id']);

        \Testo\Assert::false($schema->getComparator()->hasChanges());
    }

    #[\Testo\Test]
    public function testPrimaryKeyListedExplicitlyIsReflectedWithoutChanges(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['number', 'id']);
        $schema->save();

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->schema('auto_increment')->getPrimaryKeys(), ['number', 'id']);

        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['number', 'id']);

        \Testo\Assert::false($schema->getComparator()->hasChanges());
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('primaryTypesProvider')]
    public function testPrimaryTypesStayInPrimaryKey(string $type, string $abstractType): void
    {
        $schema = $this->schema('auto_increment');
        $schema->$type('id');
        $schema->string('title');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $saved = $this->schema('auto_increment');
        \Testo\Assert::same($saved->getPrimaryKeys(), ['id']);
        \Testo\Assert::same($saved->column('id')->getAbstractType(), $abstractType);
    }
}
