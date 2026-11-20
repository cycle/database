<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Query;

use Cycle\Database\Query\DeleteQuery;
use Cycle\Testing\Database\Assert\SqlAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

abstract class DeleteQueryTest extends DatabaseTestCase
{
    #[Test]
    public function testQueryInstance(): void
    {
        Assert::instanceOf($this->database->delete(), DeleteQuery::class);

        Assert::instanceOf($this->database->table('table')->delete(), DeleteQuery::class);

        Assert::instanceOf($this->database->table->delete(), DeleteQuery::class);
    }

    #[Test]
    public function testCompileQuery(): void
    {
        $delete = $this->db()->delete('table')
            ->where(['name' => 'Antony']);

        SqlAssert::same((string) $delete, 'DELETE FROM {table} WHERE {name} = \'Antony\'');
    }

    #[Test]
    public function testSimpleDeletion(): void
    {
        $delete = $this->database->delete()->from('table');

        SqlAssert::same($delete, 'DELETE FROM {table}');
    }

    #[Test]
    public function testDeletionWithWhere(): void
    {
        $delete = $this->database->delete()->from('table')->where('name', 'Anton');

        SqlAssert::same($delete, 'DELETE FROM {table} WHERE {name} = ?');
    }

    #[Test]
    public function testDeletionWithShortWhere(): void
    {
        $delete = $this->database->delete()->from('table')->where(['name' => 'Anton']);

        SqlAssert::same($delete, 'DELETE FROM {table} WHERE {name} = ?');
    }
}
