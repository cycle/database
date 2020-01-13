<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Query\DeleteQuery;

abstract class DeleteQueryTest extends BaseTest
{
    public function testQueryInstance(): void
    {
        $this->assertInstanceOf(
            DeleteQuery::class,
            $this->database->delete()
        );

        $this->assertInstanceOf(
            DeleteQuery::class,
            $this->database->table('table')->delete()
        );

        $this->assertInstanceOf(
            DeleteQuery::class,
            $this->database->table->delete()
        );
    }

    public function testCompileQuery(): void
    {
        $delete = $this->db()->delete('table')
            ->where(['name' => 'Antony']);

        $this->assertSameQuery(
            'DELETE FROM {table} WHERE {name} = \'Antony\'',
            (string)$delete
        );
    }

    public function testSimpleDeletion(): void
    {
        $delete = $this->database->delete()->from('table');

        $this->assertSameQuery(
            'DELETE FROM {table}',
            $delete
        );
    }

    public function testDeletionWithWhere(): void
    {
        $delete = $this->database->delete()->from('table')->where('name', 'Anton');

        $this->assertSameQuery(
            'DELETE FROM {table} WHERE {name} = ?',
            $delete
        );
    }

    public function testDeletionWithShortWhere(): void
    {
        $delete = $this->database->delete()->from('table')->where(['name' => 'Anton']);

        $this->assertSameQuery(
            'DELETE FROM {table} WHERE {name} = ?',
            $delete
        );
    }
}
