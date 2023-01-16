<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\TableTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class TableTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('set_table')->getSchema();
        $schema->primary('id');
        $schema->set('value', ['one', 'two']);
        $schema->save();
    }

    public function testInsertOneSetValue(): void
    {
        $table = $this->database->table('set_table');

        $id = $table->insertOne([
            'value' => 'one',
        ]);

        $this->assertNotNull($id);

        $this->assertEquals(
            [
                ['id' => 1, 'value' => 'one'],
            ],
            $table->fetchAll()
        );
    }

    public function testInsertMultipleSetValue(): void
    {
        $table = $this->database->table('set_table');

        $id = $table->insertOne([
            'value' => 'one,two',
        ]);

        $this->assertNotNull($id);

        $this->assertEquals(
            [
                ['id' => 1, 'value' => 'one,two'],
            ],
            $table->fetchAll()
        );
    }
}
