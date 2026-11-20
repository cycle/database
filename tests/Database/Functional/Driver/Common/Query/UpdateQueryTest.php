<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Query\UpdateQuery;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class UpdateQueryTest extends BaseTest
{
    #[\Testo\Test]
    public function testQueryInstance(): void
    {
        \Testo\Assert::instanceOf($this->database->update(), UpdateQuery::class);
        \Testo\Assert::instanceOf($this->database->table('table')->update(), UpdateQuery::class);
        \Testo\Assert::instanceOf($this->database->table->update(), UpdateQuery::class);
    }

    #[\Testo\Test]
    public function testCompileQuery(): void
    {
        $update = $this->db()
            ->update('table')
            ->set('name', 'John')
            ->where(['name' => 'Antony']);

        $this->assertSameQuery(
            "UPDATE {table} SET {name} = 'John' WHERE {name} = 'Antony'",
            (string) $update,
        );
    }

    #[\Testo\Test]
    public function testSimpleUpdate(): void
    {
        $update = $this->database->update()->in('table')->set('name', 'Anton');

        $this->assertSameQuery(
            'UPDATE {table} SET {name} = ?',
            $update,
        );
    }

    #[\Testo\Test]
    public function testSimpleUpdateAsArray(): void
    {
        $update = $this->database->update()->in('table')->values(['name' => 'Anton']);

        $this->assertSameQuery(
            'UPDATE {table} SET {name} = ?',
            $update,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhere(): void
    {
        $update = $this->database->update()->in('table')->set('name', 'Anton')->where('id', 1);

        $this->assertSameQuery(
            'UPDATE {table} SET {name} = ? WHERE {id} = ?',
            $update,
        );

        $this->assertSameParameters(
            [
                'Anton',
                1,
            ],
            $update,
        );
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($updated, 1);

        \Testo\Assert::same($this->database->select('value')
            ->from('demo')
            ->where('id', $lastID)
            ->run()
            ->fetchColumn(), 'cde');
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($this->database->select('value')
            ->from('demo')
            ->where('id', $lastID)
            ->run()
            ->fetchColumn(), null);

        $updated = $this->database->update('demo')->values(
            [
                'value' => 'abc',
            ],
        )->where('id', $lastID)->run();

        \Testo\Assert::same($updated, 1);

        \Testo\Assert::same($this->database->select('value')
            ->from('demo')
            ->where('id', $lastID)
            ->run()
            ->fetchColumn(), 'abc');
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($updated, 1);

        \Testo\Assert::same($this->database->select('value')
            ->from('demo')
            ->where('id', $lastID)
            ->run()
            ->fetchColumn(), null);
    }
}
