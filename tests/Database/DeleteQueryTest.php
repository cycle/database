<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database;

use Spiral\Database\Builders\DeleteQuery;
use Spiral\Database\Entities\Database;
use Spiral\Database\Schemas\Prototypes\AbstractTable;

abstract class DeleteQueryTest extends BaseQueryTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->database();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testQueryInstance()
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->database->delete());
        $this->assertInstanceOf(DeleteQuery::class, $this->database->table('table')->delete());
        $this->assertInstanceOf(DeleteQuery::class, $this->database->table->delete());
    }

    //Generic behaviours

    public function testSimpleDeletion()
    {
        $delete = $this->database->delete()->from('table');

        $this->assertSameQuery("DELETE FROM {table}", $delete);
    }

    public function testDeletionWithWhere()
    {
        $delete = $this->database->delete()->from('table')->where('name', 'Anton');

        $this->assertSameQuery("DELETE FROM {table} WHERE {name} = ?", $delete);
    }


    public function testDeletionWithShortWhere()
    {
        $delete = $this->database->delete()->from('table')->where(['name' => 'Anton']);

        $this->assertSameQuery("DELETE FROM {table} WHERE {name} = ?", $delete);
    }
}