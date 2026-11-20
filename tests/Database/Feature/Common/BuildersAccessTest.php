<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\Driver as DatabaseDriver;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Table;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[ExceptDriver(Driver::MySQL, Driver::SQLServer)]
class BuildersAccessTest extends DatabaseTestCase
{
    #[Test]
    public function testDriverAccess(): void
    {
        Assert::instanceOf($this->db()->getDriver(), DatabaseDriver::class);
    }

    #[Test]
    public function testTableAccess(): void
    {
        Assert::instanceOf($this->db()->table('sample'), Table::class);
    }

    #[Test]
    public function testTableSchemaAccess(): void
    {
        Assert::instanceOf($this->db()->table('sample')->getSchema(), AbstractTable::class);
    }

    #[Test]
    public function testTableDatabaseAccess(): void
    {
        Assert::equals($this->db()->table('sample')->getDatabase(), $this->db());
    }

    #[Test]
    public function testCompilerAccess(): void
    {
        Assert::instanceOf($this->db()->getDriver()->getQueryCompiler(''), CompilerInterface::class);
    }

    //via db

    #[Test]
    public function testSelectQueryAccess(): void
    {
        Assert::instanceOf($this->db()->select(), SelectQuery::class);
    }

    #[Test]
    public function testInsertQueryAccess(): void
    {
        Assert::instanceOf($this->db()->insert(), InsertQuery::class);
    }

    #[Test]
    public function testUpdateQueryAccess(): void
    {
        Assert::instanceOf($this->db()->update(), UpdateQuery::class);
    }

    #[Test]
    public function testDeleteQueryAccess(): void
    {
        Assert::instanceOf($this->db()->delete(), DeleteQuery::class);
    }

    //via table

    #[Test]
    public function testSelectQueryAccessThoughtTable(): void
    {
        Assert::instanceOf($this->db()->table('sample')->select(), SelectQuery::class);
    }

    #[Test]
    public function testSelectQueryAccessThoughtTableIterator(): void
    {
        Assert::instanceOf($this->db()->table('sample')->getIterator(), SelectQuery::class);
    }

    #[Test]
    public function testUpdateQueryAccessThoughtTable(): void
    {
        Assert::instanceOf($this->db()->table('sample')->update(), UpdateQuery::class);
    }

    #[Test]
    public function testDeleteQueryAccessThoughtTable(): void
    {
        Assert::instanceOf($this->db()->table('sample')->delete(), DeleteQuery::class);
    }
}
