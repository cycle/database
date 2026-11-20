<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Query;

use Cycle\Database\Query\UpdateQuery;
use Cycle\Testing\Database\Assert\SqlAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

abstract class UpdateQueryTest extends DatabaseTestCase
{
    #[Test]
    public function testQueryInstance(): void
    {
        Assert::instanceOf($this->database->update(), UpdateQuery::class);
        Assert::instanceOf($this->database->table('table')->update(), UpdateQuery::class);
        Assert::instanceOf($this->database->table->update(), UpdateQuery::class);
    }

    #[Test]
    public function testCompileQuery(): void
    {
        $update = $this->db()
            ->update('table')
            ->set('name', 'John')
            ->where(['name' => 'Antony']);

        SqlAssert::same((string) $update, "UPDATE {table} SET {name} = 'John' WHERE {name} = 'Antony'");
    }

    #[Test]
    public function testSimpleUpdate(): void
    {
        $update = $this->database->update()->in('table')->set('name', 'Anton');

        SqlAssert::same($update, 'UPDATE {table} SET {name} = ?');
    }

    #[Test]
    public function testSimpleUpdateAsArray(): void
    {
        $update = $this->database->update()->in('table')->values(['name' => 'Anton']);

        SqlAssert::same($update, 'UPDATE {table} SET {name} = ?');
    }

    #[Test]
    public function testUpdateWithWhere(): void
    {
        $update = $this->database->update()->in('table')->set('name', 'Anton')->where('id', 1);

        SqlAssert::same($update, 'UPDATE {table} SET {name} = ? WHERE {id} = ?');

        SqlAssert::sameParameters($update, [
            'Anton',
            1,
        ]);
    }

    #[Test]
    public function testUpdate(): void
    {
        $schema = $this->schema('demo');
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();

        $lastID = $this->database->insert('demo')->values(
            [
                'value' => 'abc',
            ],
        )->run();

        $updated = $this->database->update('demo')->values(
            [
                'value' => 'cde',
            ],
        )->where('id', $lastID)->run();

        Assert::same($updated, 1);

        Assert::same($this->database->select('value')
            ->from('demo')
            ->where('id', $lastID)
            ->run()
            ->fetchColumn(), 'cde');
    }

    #[Test]
    public function testUpdateToNotNull(): void
    {
        $schema = $this->schema('demo');
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();

        $lastID = $this->database->insert('demo')->values(
            [
                'value' => null,
            ],
        )->run();

        Assert::same($this->database->select('value')
            ->from('demo')
            ->where('id', $lastID)
            ->run()
            ->fetchColumn(), null);

        $updated = $this->database->update('demo')->values(
            [
                'value' => 'abc',
            ],
        )->where('id', $lastID)->run();

        Assert::same($updated, 1);

        Assert::same($this->database->select('value')
            ->from('demo')
            ->where('id', $lastID)
            ->run()
            ->fetchColumn(), 'abc');
    }

    #[Test]
    public function testUpdateToNull(): void
    {
        $schema = $this->schema('demo');
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();

        $lastID = $this->database->insert('demo')->values(
            [
                'value' => 'abc',
            ],
        )->run();

        $updated = $this->database->update('demo')->values(
            [
                'value' => null,
            ],
        )->where('id', $lastID)->run();

        Assert::same($updated, 1);

        Assert::same($this->database->select('value')
            ->from('demo')
            ->where('id', $lastID)
            ->run()
            ->fetchColumn(), null);
    }
}
