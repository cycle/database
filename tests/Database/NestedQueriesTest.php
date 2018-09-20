<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Query\Interpolator;
use Spiral\Database\Injection\Expression;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;
use Spiral\Database\Query\AbstractQuery;
use Spiral\Database\Query\SelectQuery;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Pagination\PaginatorAwareInterface;

abstract class NestedQueriesTest extends BaseQueryTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->db();
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testQueryInstance()
    {
        $this->assertInstanceOf(SelectQuery::class, $this->database->select());
        $this->assertInstanceOf(SelectQuery::class, $this->database->table('table')->select());
        $this->assertInstanceOf(SelectQuery::class, $this->database->table->select());
        $this->assertInstanceOf(\IteratorAggregate::class, $this->database->table->select());
        $this->assertInstanceOf(PaginatorAwareInterface::class, $this->database->table->select());
    }

    public function testSimpleSelection()
    {
        $select = $this->database->select()->from('table');

        $this->assertSameQuery("SELECT * FROM {table}", $select);
        $this->assertSameParameters([], $select);
    }

    public function testSimpleWhere()
    {
        $select = $this->database->select()->from('table')->where('id', 1);

        $this->assertSameQuery("SELECT * FROM {table} WHERE {id} = ?", $select);
        $this->assertSameParameters([
            1
        ], $select);
    }

    public function testWhereAndJoin()
    {
        $select = $this->database->select()
            ->from('table')
            ->leftJoin('external')->onWhere(['name' => 'test'])
            ->where('id', 1);

        $this->assertSameQuery("SELECT * FROM {table} LEFT JOIN {external} ON {name} = ? WHERE {id} = ?",
            $select);
        $this->assertSameParameters([
            'test',
            1
        ], $select);
    }

    public function testWhereAndJoinReverted()
    {
        $select = $this->database->select()
            ->from('table')
            ->where('id', 1)
            ->leftJoin('external')->onWhere(['name' => 'test']);

        $this->assertSameQuery("SELECT * FROM {table} LEFT JOIN {external} ON {name} = ? WHERE {id} = ?",
            $select);
        $this->assertSameParameters([
            'test',
            1
        ], $select);
    }

    public function testArrayWhere()
    {
        $select = $this->database->select()->from('table')
            ->where('id', 'IN', new Parameter([1, 2, 3, 4]));

        $this->assertSameQuery("SELECT * FROM {table} WHERE {id} IN (?, ?, ?, ?)", $select);
        $this->assertSameParameters([
            1,
            2,
            3,
            4
        ], $select);
    }

    public function testSubQuery()
    {
        $select = $this->database->select()
            ->from('table')
            ->where('type', 'user')
            ->where(
                'id',
                'IN', $this->database->select('user_id')->from('accounts')->where('open', true)
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {type} = ? AND {id} IN (
              SELECT {user_id} FROM {accounts} WHERE {open} = ?
            ) OR {id} < ?",
            $select
        );

        $this->assertSameParameters([
            'user',
            true,
            100
        ], $select);
    }

    public function testSubQueryPrefixed()
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from('table')
            ->where('type', 'user')
            ->where(
                'id',
                'IN', $this->database->select('user_id')->from('accounts')->where('open', true)
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            "SELECT * FROM {prefix_table} WHERE {type} = ? AND {id} IN (
              SELECT {user_id} FROM {prefix_accounts} WHERE {open} = ?
            ) OR {id} < ?",
            $select
        );

        $this->assertSameParameters([
            'user',
            true,
            100
        ], $select);
    }

    public function testSubQueryPrefixedWithExpression()
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from('table AS u')
            ->where('type', 'user')
            ->where(
                'id',
                'IN', $this->database->select('user_id')->from('accounts')->where('open', true)
                ->andWhere('pay_id', new Expression('u.id'))
            )->orWhere('table.id', '<', 100);

        $this->assertSameQuery(
            "SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? AND {id} IN (
              SELECT {user_id} FROM {prefix_accounts} WHERE {open} = ? AND {pay_id} = {u}.{id}
            ) OR {prefix_table}.{id} < ?",
            $select
        );

        $this->assertSameParameters([
            'user',
            true,
            100
        ], $select);
    }

    public function testUnionWithPrefixes()
    {
        $select = $this->db('prefixed', 'prefix_')
            ->select('*')
            ->from('table AS u')
            ->where('type', 'user')->orWhere('table.id', '<', 100);

        $select->union(
            $this->db('prefixed', 'prefix_2_')
                ->select('*')
                ->from('table AS u')
                ->where('type', 'admin')->orWhere('table.id', '>', 800)
        );

        $this->assertSameQuery(
            "SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? OR {prefix_table}.{id} < ?
             UNION 
             (SELECT * FROM {prefix_2_table} AS {u} WHERE {type} = ? OR {prefix_2_table}.{id} > ?)",
            $select
        );

        $this->assertSameParameters([
            'user',
            100,
            'admin',
            800
        ], $select);
    }

    public function testUnionWithPrefixes1()
    {
        $select = $this->db('prefixed', 'prefix_')
            ->select('*')
            ->from('table AS u')
            ->where('type', 'user')->orWhere('table.id', '<', 100);

        $select->unionAll(
            $this->db('prefixed', 'prefix_2_')
                ->select('*')
                ->from('table AS u')
                ->where('type', 'admin')->orWhere('table.id', '>', 800)
        );

        $this->assertSameQuery(
            "SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? OR {prefix_table}.{id} < ?
             UNION ALL
             (SELECT * FROM {prefix_2_table} AS {u} WHERE {type} = ? OR {prefix_2_table}.{id} > ?)",
            $select
        );

        $this->assertSameParameters([
            'user',
            100,
            'admin',
            800
        ], $select);
    }

    public function testUnionWithPrefixes2()
    {
        $select = $this->db('prefixed', 'prefix_')
            ->select('*')
            ->from('table AS u')
            ->where('type', 'user')->orWhere('table.id', '<', 100);

        $select->union(
            $this->db('prefixed', 'prefix_2_')
                ->select('*')
                ->from('table AS u')
                ->where('type', 'admin')->orWhere('table.id', '>', 800)
        );

        $select->unionAll(
            $this->db('prefixed', 'prefix_3_')->select('*')
                ->from('table')->where('x', 'IN', new Parameter([8, 9, 10]))
        );

        $this->assertSameQuery(
            "SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? OR {prefix_table}.{id} < ?
             UNION
             (SELECT * FROM {prefix_2_table} AS {u} WHERE {type} = ? OR {prefix_2_table}.{id} > ?)
             UNION ALL
             (SELECT * FROM {prefix_3_table} WHERE {x} IN (?, ?, ?))",
            $select
        );

        $this->assertSameParameters([
            'user',
            100,
            'admin',
            800,
            8,
            9,
            10
        ], $select);
    }

    public function testSubQueryInUpdate()
    {
        $select = $this->database->update()
            ->in('table')
            ->set('name', 'Anton')
            ->set('value',
                $this->database->select(new Expression('SUM(value)'))
                    ->from('transactions')
                    ->where('user_id', new Expression('table.id'))
                    ->where('case', 'open')
            )
            ->where('type', 'user')
            ->where(
                'id',
                'IN', $this->database->select('user_id')->from('accounts')->where('open', true)
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            "UPDATE {table} SET
             {name} = ?, 
             {value} = (SELECT SUM({value}) FROM {transactions} WHERE {user_id} = {table}.{id} AND {case} = ?)
             WHERE {type} = ? AND {id} IN (
                SELECT {user_id} FROM {accounts} WHERE {open} = ?
             ) OR {id} < ?",
            $select
        );

        $this->assertSameParameters([
            'Anton',
            'open',
            'user',
            true,
            100
        ], $select);
    }

    protected function assertSameParameters(array $parameters, AbstractQuery $builder)
    {
        $builderParameters = [];
        foreach (Interpolator::flattenParameters($builder->getParameters()) as $value) {
            $this->assertInstanceOf(ParameterInterface::class, $value);
            $this->assertFalse($value->isArray());

            $builderParameters[] = $value->getValue();
        }

        $this->assertEquals($parameters, $builderParameters);
    }
}