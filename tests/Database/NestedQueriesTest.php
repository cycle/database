<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Injection\Expression;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\SelectQuery;

abstract class NestedQueriesTest extends BaseTest
{
    public function testSubQuery(): void
    {
        $select = $this->database->select()
            ->from('table')
            ->where('type', 'user')
            ->where(
                'id',
                'IN',
                $this->database
                    ->select('user_id')
                    ->from('accounts')
                    ->where('open', true)
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            'SELECT * FROM {table} WHERE {type} = ? AND {id} IN (
                      SELECT {user_id} FROM {accounts} WHERE {open} = ?
                    ) OR {id} < ?',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                true,
                100
            ],
            $select
        );
    }

    public function testSubQueryPrefixed(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from('table')
            ->where('type', 'user')
            ->where(
                'id',
                'IN',
                $this->database->select('user_id')
                    ->from('accounts')->where('open', true)
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            'SELECT * FROM {prefix_table} WHERE {type} = ? AND {id} IN (
              SELECT {user_id} FROM {accounts} WHERE {open} = ?
            ) OR {id} < ?',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                true,
                100
            ],
            $select
        );
    }

    public function testSubQueryPrefixedAsIndentifier(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from('table')
            ->where('type', 'user')
            ->where(
                $this->database
                    ->select('COUNT(user_id)')
                    ->from('accounts')
                    ->where('open', true),
                '>',
                12
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            'SELECT * FROM {prefix_table} WHERE {type} = ? AND (
              SELECT COUNT({user_id}) FROM {accounts} WHERE {open} = ?
            ) > ? OR {id} < ?',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                true,
                12,
                100
            ],
            $select
        );
    }

    public function testSubQueryPrefixedRawIdentifier(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from('table')
            ->where('type', 'user')
            ->where(
                (new SelectQuery())
                    ->columns('COUNT(user_id)')
                    ->from('accounts')
                    ->where('open', true),
                '>',
                12
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            'SELECT * FROM {prefix_table} WHERE {type} = ? AND (
              SELECT COUNT({user_id}) FROM {prefix_accounts} WHERE {open} = ?
            ) > ? OR {id} < ?',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                true,
                12,
                100
            ],
            $select
        );
    }

    public function testSubQueryPrefixedRaw(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from('table')
            ->where('type', 'user')
            ->where(
                'id',
                'IN',
                (new SelectQuery())->columns('user_id')
                    ->from('accounts')
                    ->where('open', true)
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            'SELECT * FROM {prefix_table} WHERE {type} = ? AND {id} IN (
                  SELECT {user_id} FROM {prefix_accounts} WHERE {open} = ?
                ) OR {id} < ?',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                true,
                100
            ],
            $select
        );
    }


    public function testSubQueryPrefixedWithExpression(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from('table AS u')
            ->where('type', 'user')
            ->where(
                'id',
                'IN',
                $this->database->select('user_id')
                    ->from('accounts')
                    ->where('open', true)
                    ->andWhere('pay_id', new Expression('u.id'))
            )->orWhere('table.id', '<', 100);

        $this->assertSameQuery(
            'SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? AND {id} IN (
                  SELECT {user_id} FROM {accounts} WHERE {open} = ? AND {pay_id} = {u}.{id}
                ) OR {prefix_table}.{id} < ?',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                true,
                100
            ],
            $select
        );
    }


    public function testSubQueryPrefixedWithExpressionId(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from('table AS u')
            ->where('type', 'user')
            ->where(
                (new SelectQuery())->from('accounts')
                    ->columns(new Expression('COUNT(user_id)'))
                    ->where('accounts.open', true)
                    ->andWhere('pay_id', new Expression('u.id')),
                '>',
                0
            )->orWhere('table.id', '<', 100);

        $this->assertSameQuery(
            'SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? AND (
                  SELECT COUNT({user_id}) FROM {prefix_accounts} WHERE
                  {prefix_accounts}.{open} = ? AND {pay_id} = {u}.{id}
                ) > ? OR {prefix_table}.{id} < ?',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                true,
                0,
                100
            ],
            $select
        );
    }

    public function testUnionWithPrefixes(): void
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
            'SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? OR {prefix_table}.{id} < ?
                     UNION
                     (SELECT * FROM {prefix_2_table} AS {u} WHERE {type} = ? OR {prefix_2_table}.{id} > ?)',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                100,
                'admin',
                800
            ],
            $select
        );
    }

    public function testUnionWithPrefixes1(): void
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
            'SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? OR {prefix_table}.{id} < ?
                     UNION ALL
                     (SELECT * FROM {prefix_2_table} AS {u} WHERE {type} = ? OR {prefix_2_table}.{id} > ?)',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                100,
                'admin',
                800
            ],
            $select
        );
    }

    public function testUnionWithPrefixes2(): void
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
            'SELECT * FROM {prefix_table} AS {u} WHERE {type} = ? OR {prefix_table}.{id} < ?
                     UNION
                     (SELECT * FROM {prefix_2_table} AS {u} WHERE {type} = ? OR {prefix_2_table}.{id} > ?)
                     UNION ALL
                     (SELECT * FROM {prefix_3_table} WHERE {x} IN (?, ?, ?))',
            $select
        );

        $this->assertSameParameters(
            [
                'user',
                100,
                'admin',
                800,
                8,
                9,
                10
            ],
            $select
        );
    }

    public function testSubQueryInUpdate(): void
    {
        $select = $this->database->update()
            ->in('table')
            ->set('name', 'Anton')
            ->set(
                'value',
                $this->database->select(new Expression('SUM(value)'))
                    ->from('transactions')
                    ->where('user_id', new Expression('table.id'))
                    ->where('case', 'open')
            )
            ->where('type', 'user')
            ->where(
                'id',
                'IN',
                $this->database
                    ->select('user_id')
                    ->from('accounts')
                    ->where('open', true)
            )->orWhere('id', '<', 100);

        $this->assertSameQuery(
            'UPDATE {table} SET
                     {name} = ?,
                     {value} = (SELECT SUM({value}) FROM {transactions} WHERE {user_id} = {table}.{id} AND {case} = ?)
                     WHERE {type} = ? AND {id} IN (
                        SELECT {user_id} FROM {accounts} WHERE {open} = ?
                     ) OR {id} < ?',
            $select
        );

        $this->assertSameParameters(
            [
                'Anton',
                'open',
                'user',
                true,
                100
            ],
            $select
        );
    }
}
