<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Table;

abstract class BuildersAccessTest extends BaseTest
{
    #[\Testo\Test]
    public function testDriverAccess(): void
    {
        \Testo\Assert::instanceOf($this->db()->getDriver(), Driver::class);
    }

    #[\Testo\Test]
    public function testTableAccess(): void
    {
        \Testo\Assert::instanceOf($this->db()->table('sample'), Table::class);
    }

    #[\Testo\Test]
    public function testTableSchemaAccess(): void
    {
        \Testo\Assert::instanceOf($this->db()->table('sample')->getSchema(), AbstractTable::class);
    }

    #[\Testo\Test]
    public function testTableDatabaseAccess(): void
    {
        \Testo\Assert::equals($this->db()->table('sample')->getDatabase(), $this->db());
    }

    #[\Testo\Test]
    public function testCompilerAccess(): void
    {
        \Testo\Assert::instanceOf($this->db()->getDriver()->getQueryCompiler(''), CompilerInterface::class);
    }

    //via db

    #[\Testo\Test]
    public function testSelectQueryAccess(): void
    {
        \Testo\Assert::instanceOf($this->db()->select(), SelectQuery::class);
    }

    #[\Testo\Test]
    public function testInsertQueryAccess(): void
    {
        \Testo\Assert::instanceOf($this->db()->insert(), InsertQuery::class);
    }

    #[\Testo\Test]
    public function testUpdateQueryAccess(): void
    {
        \Testo\Assert::instanceOf($this->db()->update(), UpdateQuery::class);
    }

    #[\Testo\Test]
    public function testDeleteQueryAccess(): void
    {
        \Testo\Assert::instanceOf($this->db()->delete(), DeleteQuery::class);
    }

    //via table

    #[\Testo\Test]
    public function testSelectQueryAccessThoughtTable(): void
    {
        \Testo\Assert::instanceOf($this->db()->table('sample')->select(), SelectQuery::class);
    }

    #[\Testo\Test]
    public function testSelectQueryAccessThoughtTableIterator(): void
    {
        \Testo\Assert::instanceOf($this->db()->table('sample')->getIterator(), SelectQuery::class);
    }

    #[\Testo\Test]
    public function testUpdateQueryAccessThoughtTable(): void
    {
        \Testo\Assert::instanceOf($this->db()->table('sample')->update(), UpdateQuery::class);
    }

    #[\Testo\Test]
    public function testDeleteQueryAccessThoughtTable(): void
    {
        \Testo\Assert::instanceOf($this->db()->table('sample')->delete(), DeleteQuery::class);
    }
}
