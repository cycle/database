<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Driver\Handler;
use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\DatetimeColumnTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class DatetimeColumnTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public static function fractionalCurrentTimestampProvider(): iterable
    {
        yield 'datetime(6)' => ['datetime', 6];
        yield 'datetime(3)' => ['datetime', 3];
        yield 'timestamp(6)' => ['timestamp', 6];
    }

    public function testTimestampDatetimeZero(): void
    {
        $this->expectExceptionMessage(
            "SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid default value for 'target'",
        );

        $this->expectException(HandlerException::class);
        parent::testTimestampDatetimeZero();
    }

    public function testTimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->time('time_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('time', $schema->column('time_data')->getInternalType());
        $this->assertSame(3, $schema->column('time_data')->getSize());
    }

    public function testTimestampWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->timestamp('timestamp_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('timestamp', $schema->column('timestamp_data')->getInternalType());
        $this->assertSame(3, $schema->column('timestamp_data')->getSize());
    }

    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', size: 3);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $this->assertSame('datetime', $schema->column('datetime_data')->getInternalType());
        $this->assertSame(3, $schema->column('datetime_data')->getSize());
    }

    /**
     * @dataProvider fractionalCurrentTimestampProvider
     */
    public function testCurrentTimestampWithSize(string $type, int $size): void
    {
        $schema = $this->schema('table');
        $schema->primary('id');
        $schema->$type('target', size: $size)->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);

        $saved = $this->schema('table');
        $this->assertSame($size, $saved->column('target')->getSize());
        $this->assertEquals(
            new Fragment(AbstractColumn::DATETIME_NOW),
            $saved->column('target')->getDefaultValue(),
        );

        $saved->$type('target', size: $size)->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $this->assertFalse($saved->getComparator()->hasChanges());

        $this->database->table('table')->insertOne(['id' => 1]);
        $this->assertNotNull($this->database->table('table')->select('target')->fetchAll()[0]['target']);
    }

    public function testExistingCurrentTimestampWithSizeIsReflected(): void
    {
        $this->database->execute(
            'CREATE TABLE `table` (
                `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `target` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
            )',
        );

        $schema = $this->schema('table');
        $this->assertEquals(
            new Fragment(AbstractColumn::DATETIME_NOW),
            $schema->column('target')->getDefaultValue(),
        );

        $schema->datetime('target', size: 6)->nullable(false)->defaultValue(AbstractColumn::DATETIME_NOW);
        $this->assertFalse($schema->getComparator()->hasChanges());
    }
}
