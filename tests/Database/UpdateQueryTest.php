<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\AbstractQuery;
use Spiral\Database\Query\Interpolator;
use Spiral\Database\Query\UpdateQuery;
use Spiral\Database\Schema\AbstractTable;

abstract class UpdateQueryTest extends BaseQueryTest
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
        $this->assertInstanceOf(UpdateQuery::class, $this->database->update());
        $this->assertInstanceOf(UpdateQuery::class, $this->database->table('table')->update());
        $this->assertInstanceOf(UpdateQuery::class, $this->database->table->update());
    }

    //Generic behaviours

    public function testSimpleUpdate(): void
    {
        $update = $this->database->update()->in('table')->set('name', 'Anton');

        $this->assertSameQuery('UPDATE {table} SET {name} = ?', $update);
    }

    public function testSimpleUpdateAsArray(): void
    {
        $update = $this->database->update()->in('table')->values(['name' => 'Anton']);

        $this->assertSameQuery('UPDATE {table} SET {name} = ?', $update);
    }

    public function testUpdateWithWhere(): void
    {
        $update = $this->database->update()->in('table')->set('name', 'Anton')->where('id', 1);

        $this->assertSameQuery('UPDATE {table} SET {name} = ? WHERE {id} = ?', $update);

        $this->assertSameParameters([
            'Anton',
            1
        ], $update);
    }

    public function testUpdate(): void
    {
        $schema = $this->schema('demo');
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();

        $lastID = $this->database->insert('demo')->values([
            'value' => 'abc'
        ])->run();

        $updated = $this->database->update('demo')->values([
            'value' => 'cde'
        ])->where('id', $lastID)->run();

        $this->assertSame(1, $updated);

        $this->assertSame(
            'cde',
            $this->database->select('value')
                ->from('demo')
                ->where('id', $lastID)
                ->run()
                ->fetchColumn()
        );
    }

    public function testUpdateToNotNull(): void
    {
        $schema = $this->schema('demo');
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();

        $lastID = $this->database->insert('demo')->values([
            'value' => null
        ])->run();

        $this->assertSame(
            null,
            $this->database->select('value')
                ->from('demo')
                ->where('id', $lastID)
                ->run()
                ->fetchColumn()
        );

        $updated = $this->database->update('demo')->values([
            'value' => 'abc'
        ])->where('id', $lastID)->run();

        $this->assertSame(1, $updated);

        $this->assertSame(
            'abc',
            $this->database->select('value')
                ->from('demo')
                ->where('id', $lastID)
                ->run()
                ->fetchColumn()
        );
    }

    public function testUpdateToNull(): void
    {
        $schema = $this->schema('demo');
        $schema->primary('id');
        $schema->string('value')->nullable();
        $schema->save();

        $lastID = $this->database->insert('demo')->values([
            'value' => 'abc'
        ])->run();

        $updated = $this->database->update('demo')->values([
            'value' => null
        ])->where('id', $lastID)->run();

        $this->assertSame(1, $updated);

        $this->assertSame(
            null,
            $this->database->select('value')
                ->from('demo')
                ->where('id', $lastID)
                ->run()
                ->fetchColumn()
        );
    }

    protected function assertSameParameters(array $parameters, AbstractQuery $query): void
    {
        $builderParameters = [];
        foreach (Interpolator::flattenParameters($query->getParameters()) as $value) {
            $this->assertInstanceOf(ParameterInterface::class, $value);
            $this->assertFalse($value->isArray());

            $builderParameters[] = $value->getValue();
        }

        $this->assertEquals($parameters, $builderParameters);
    }
}
