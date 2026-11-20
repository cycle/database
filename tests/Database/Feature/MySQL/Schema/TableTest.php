<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\TableTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class TableTest extends CommonClass
{
    #[Test]
    public function testInsertOneSetValue(): void
    {
        $table = $this->database->table('set_table');

        $id = $table->insertOne([
            'value' => 'one',
        ]);

        Assert::notNull($id);

        Assert::equals($table->fetchAll(), [
            ['id' => 1, 'value' => 'one'],
        ]);
    }

    #[Test]
    public function testInsertMultipleSetValue(): void
    {
        $table = $this->database->table('set_table');

        $id = $table->insertOne([
            'value' => 'one,two',
        ]);

        Assert::notNull($id);

        Assert::equals($table->fetchAll(), [
            ['id' => 1, 'value' => 'one,two'],
        ]);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->table('set_table')->getSchema();
        $schema->primary('id');
        $schema->set('value', ['one', 'two']);
        $schema->save();
    }
}
