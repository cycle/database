<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-mysql
 */
final class AutoIncrementColumnTest extends BaseTest
{
    public const DRIVER = 'mysql';

    public static function primaryTypesProvider(): iterable
    {
        yield 'primary' => ['primary', 'primary'];
        yield 'smallPrimary' => ['smallPrimary', 'smallPrimary'];
        yield 'bigPrimary' => ['bigPrimary', 'bigPrimary'];
    }

    public function testAutoIncrementColumnIsNotAddedToPrimaryKey(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true);
        $schema->bigInteger('big_number', autoIncrement: true);
        $schema->setPrimaryKeys(['id']);

        $this->assertSame(['id'], $schema->getPrimaryKeys());
        $this->assertSame('integer', $schema->column('number')->getAbstractType());
        $this->assertSame('bigInteger', $schema->column('big_number')->getAbstractType());
    }

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
        $this->assertSame(['id'], $saved->getPrimaryKeys());
        $this->assertSame('integer', $saved->column('number')->getAbstractType());
        $this->assertTrue($saved->hasIndex(['number']));

        $table = $this->database->table('auto_increment');
        $table->insertOne(['id' => 'a', 'title' => 'first']);
        $table->insertOne(['id' => 'b', 'title' => 'second']);
        $this->assertSame(
            [['id' => 'a', 'number' => 1], ['id' => 'b', 'number' => 2]],
            $table->select('id', 'number')->orderBy('id')->fetchAll(),
        );
    }

    public function testCreateTableWithNonUniqueIndexOnAutoIncrementColumn(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->bigInteger('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id']);
        $schema->index(['number']);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(['id'], $this->schema('auto_increment')->getPrimaryKeys());
    }

    public function testCreateTableWithAutoIncrementColumnNotFirstInPrimaryKey(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id', 'number']);
        $schema->index(['number'])->unique();
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(['id', 'number'], $this->schema('auto_increment')->getPrimaryKeys());
    }

    public function testAutoIncrementColumnWithoutKeyThrowsException(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['id']);

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('`number`');

        $schema->save();
    }

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
        $this->assertSame(['id'], $schema->getPrimaryKeys());
        $this->assertSame('integer', $schema->column('number')->getAbstractType());

        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->index(['number'])->unique()->setName('auto_increment_number');
        $schema->setPrimaryKeys(['id']);

        $this->assertFalse($schema->getComparator()->hasChanges());
    }

    public function testPrimaryKeyListedExplicitlyIsReflectedWithoutChanges(): void
    {
        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['number', 'id']);
        $schema->save();

        $this->assertSameAsInDB($schema);
        $this->assertSame(['number', 'id'], $this->schema('auto_increment')->getPrimaryKeys());

        $schema = $this->schema('auto_increment');
        $schema->string('id', 36)->nullable(false);
        $schema->integer('number', autoIncrement: true)->nullable(false);
        $schema->setPrimaryKeys(['number', 'id']);

        $this->assertFalse($schema->getComparator()->hasChanges());
    }

    /**
     * @dataProvider primaryTypesProvider
     */
    public function testPrimaryTypesStayInPrimaryKey(string $type, string $abstractType): void
    {
        $schema = $this->schema('auto_increment');
        $schema->$type('id');
        $schema->string('title');
        $schema->save();

        $this->assertSameAsInDB($schema);

        $saved = $this->schema('auto_increment');
        $this->assertSame(['id'], $saved->getPrimaryKeys());
        $this->assertSame($abstractType, $saved->column('id')->getAbstractType());
    }
}
