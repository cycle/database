<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Query\DeleteQuery;
use Spiral\Database\Database;
use Spiral\Database\Schema\AbstractTable;

abstract class DeleteQueryTest extends BaseQueryTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp(): void
    {
        $this->database = $this->db();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testQueryInstance(): void
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->database->delete());
        $this->assertInstanceOf(DeleteQuery::class, $this->database->table('table')->delete());
        $this->assertInstanceOf(DeleteQuery::class, $this->database->table->delete());
    }

    //Generic behaviours

    public function testSimpleDeletion(): void
    {
        $delete = $this->database->delete()->from('table');

        $this->assertSameQuery('DELETE FROM {table}', $delete);
    }

    public function testDeletionWithWhere(): void
    {
        $delete = $this->database->delete()->from('table')->where('name', 'Anton');

        $this->assertSameQuery('DELETE FROM {table} WHERE {name} = ?', $delete);
    }


    public function testDeletionWithShortWhere(): void
    {
        $delete = $this->database->delete()->from('table')->where(['name' => 'Anton']);

        $this->assertSameQuery('DELETE FROM {table} WHERE {name} = ?', $delete);
    }
}
