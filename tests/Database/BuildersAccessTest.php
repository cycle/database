<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Spiral\Database\Driver\AbstractDriver;
use Spiral\Database\Driver\Compiler;
use Spiral\Database\Query\DeleteQuery;
use Spiral\Database\Query\InsertQuery;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\Query\UpdateQuery;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\Table;

abstract class BuildersAccessTest extends BaseTest
{
    public function testDriverAccess()
    {
        $this->assertInstanceOf(AbstractDriver::class, $this->db()->getDriver());
    }

    public function testTableAccess()
    {
        $this->assertInstanceOf(
            Table::class,
            $this->db()->table('sample')
        );
    }

    public function testTableSchemaAccess()
    {
        $this->assertInstanceOf(
            AbstractTable::class,
            $this->db()->table('sample')->getSchema()
        );
    }

    public function testTableDatabaseAccess()
    {
        $this->assertEquals(
            $this->db(),
            $this->db()->table('sample')->getDatabase()
        );
    }

    public function testCompilerAccess()
    {
        $this->assertInstanceOf(
            Compiler::class,
            $this->db()->getDriver()->getCompiler('')
        );
    }

    //via db

    public function testSelectQueryAccess()
    {
        $this->assertInstanceOf(SelectQuery::class, $this->db()->select());
    }

    public function testInsertQueryAccess()
    {
        $this->assertInstanceOf(InsertQuery::class, $this->db()->insert());
    }

    public function testUpdateQueryAccess()
    {
        $this->assertInstanceOf(UpdateQuery::class, $this->db()->update());
    }

    public function testDeleteQueryAccess()
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->db()->delete());
    }

    //via table

    public function testSelectQueryAccessThoughtTable()
    {
        $this->assertInstanceOf(SelectQuery::class, $this->db()->table('sample')->select());
    }

    public function testSelectQueryAccessThoughtTableIterator()
    {
        $this->assertInstanceOf(SelectQuery::class, $this->db()->table('sample')->getIterator());
    }

    public function testUpdateQueryAccessThoughtTable()
    {
        $this->assertInstanceOf(UpdateQuery::class, $this->db()->table('sample')->update());
    }

    public function testDeleteQueryAccessThoughtTable()
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->db()->table('sample')->delete());
    }
}