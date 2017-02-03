<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database;

use Spiral\Database\Builders\DeleteQuery;
use Spiral\Database\Builders\InsertQuery;
use Spiral\Database\Builders\SelectQuery;
use Spiral\Database\Builders\UpdateQuery;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Entities\QueryCompiler;
use Spiral\Database\Entities\Table;
use Spiral\Database\Schemas\Prototypes\AbstractTable;

abstract class BuildersAccessTest extends BaseTest
{
    public function testDriverAccess()
    {
        $this->assertInstanceOf(Driver::class, $this->database()->getDriver());
    }

    public function testTableAccess()
    {
        $this->assertInstanceOf(
            Table::class,
            $this->database()->table('sample')
        );
    }

    public function testTableSchemaAccess()
    {
        $this->assertInstanceOf(
            AbstractTable::class,
            $this->database()->table('sample')->getSchema()
        );
    }

    public function testTableDatabaseAccess()
    {
        $this->assertEquals(
            $this->database(),
            $this->database()->table('sample')->getDatabase()
        );
    }

    public function testCompilerAccess()
    {
        $this->assertInstanceOf(
            QueryCompiler::class,
            $this->database()->getDriver()->queryCompiler('')
        );
    }

    //via db

    public function testSelectQueryAccess()
    {
        $this->assertInstanceOf(SelectQuery::class, $this->database()->select());
    }

    public function testInsertQueryAccess()
    {
        $this->assertInstanceOf(InsertQuery::class, $this->database()->insert());
    }

    public function testUpdateQueryAccess()
    {
        $this->assertInstanceOf(UpdateQuery::class, $this->database()->update());
    }

    public function testDeleteQueryAccess()
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->database()->delete());
    }

    //via table

    public function testSelectQueryAccessThoughtTable()
    {
        $this->assertInstanceOf(SelectQuery::class, $this->database()->table('sample')->select());
    }

    public function testSelectQueryAccessThoughtTableIterator()
    {
        $this->assertInstanceOf(SelectQuery::class, $this->database()->table('sample')->getIterator());
    }

    public function testUpdateQueryAccessThoughtTable()
    {
        $this->assertInstanceOf(UpdateQuery::class, $this->database()->table('sample')->update());
    }

    public function testDeleteQueryAccessThoughtTable()
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->database()->table('sample')->delete());
    }
}