<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class DeleteQueryTest extends BaseTest
{
    #[\Testo\Test]
    public function testQueryInstance(): void
    {
        \Testo\Assert::instanceOf($this->database->delete(), DeleteQuery::class);

        \Testo\Assert::instanceOf($this->database->table('table')->delete(), DeleteQuery::class);

        \Testo\Assert::instanceOf($this->database->table->delete(), DeleteQuery::class);
    }

    #[\Testo\Test]
    public function testCompileQuery(): void
    {
        $delete = $this->db()->delete('table')
            ->where(['name' => 'Antony']);

        $this->assertSameQuery(
            'DELETE FROM {table} WHERE {name} = \'Antony\'',
            (string) $delete,
        );
    }

    #[\Testo\Test]
    public function testSimpleDeletion(): void
    {
        $delete = $this->database->delete()->from('table');

        $this->assertSameQuery(
            'DELETE FROM {table}',
            $delete,
        );
    }

    #[\Testo\Test]
    public function testDeletionWithWhere(): void
    {
        $delete = $this->database->delete()->from('table')->where('name', 'Anton');

        $this->assertSameQuery(
            'DELETE FROM {table} WHERE {name} = ?',
            $delete,
        );
    }

    #[\Testo\Test]
    public function testDeletionWithShortWhere(): void
    {
        $delete = $this->database->delete()->from('table')->where(['name' => 'Anton']);

        $this->assertSameQuery(
            'DELETE FROM {table} WHERE {name} = ?',
            $delete,
        );
    }
}
