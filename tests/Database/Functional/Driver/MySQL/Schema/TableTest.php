<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\TableTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class TableTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testInsertOneSetValue(): void
    {
        $table = $this->database->table('set_table');

        $id = $table->insertOne([
            'value' => 'one',
        ]);

        \Testo\Assert::notNull($id);

        \Testo\Assert::equals($table->fetchAll(), [
            ['id' => 1, 'value' => 'one'],
        ]);
    }

    #[\Testo\Test]
    public function testInsertMultipleSetValue(): void
    {
        $table = $this->database->table('set_table');

        $id = $table->insertOne([
            'value' => 'one,two',
        ]);

        \Testo\Assert::notNull($id);

        \Testo\Assert::equals($table->fetchAll(), [
            ['id' => 1, 'value' => 'one,two'],
        ]);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('set_table')->getSchema();
        $schema->primary('id');
        $schema->set('value', ['one', 'two']);
        $schema->save();
    }
}
