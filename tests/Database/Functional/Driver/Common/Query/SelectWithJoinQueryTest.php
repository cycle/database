<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class SelectWithJoinQueryTest extends BaseTest
{
    public function testWhereAndJoin(): void
    {
        $select = $this->database->select()
            ->from('table')
            ->leftJoin('external')->onWhere(['name' => 'test'])
            ->where('id', 1);

        $this->assertSameQueryWithParameters(
            'SELECT * FROM {table} LEFT JOIN {external} ON {name} = ? WHERE {id} = ?',
            [
                'test',
                1,
            ],
            $select
        );
    }

    public function testWhereAndJoinReverted(): void
    {
        $select = $this->database->select()
            ->from('table')
            ->where('id', 1)
            ->leftJoin('external')->onWhere(['name' => 'test']);

        $this->assertSameQueryWithParameters(
            'SELECT * FROM {table} LEFT JOIN {external} ON {name} = ? WHERE {id} = ?',
            [
                'test',
                1,
            ],
            $select
        );
    }

    public function testLeftJoin0(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('LEFT', 'photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testLeftJoin1(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('LEFT', 'photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testLeftJoin2(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testLeftJoin3(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testLeftJoin4(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('LEFT', 'photos', 'pht', ['pht.user_id', 'users.id']);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos} AS {pht} ON {pht}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testJoinOn1(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('LEFT', 'photos', 'pht', [
                '@and' => [
                    ['pht.user_id' => 'users.id'],
                ],
            ]);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos} AS {pht} ON ({pht}.{user_id} = {users}.{id})',
            $select
        );
    }

    public function testJoinOn2(): void
    {
        $select = $this->database->select()
            ->from(['users', 'admins'])
            ->join('LEFT', 'photos', 'pht', [
                [
                    '@or' => [
                        [
                            'pht.user_id' => 'users.id',
                            'users.is_admin' => new Parameter(true),
                        ],
                        [
                            '@or' => [
                                ['pht.user_id' => 'users.parent_id'],
                                ['users.is_admin' => new Parameter(false)],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertSameQueryWithParameters(
            'SELECT * FROM {users}, {admins} LEFT JOIN {photos} AS {pht}
                    ON (
                        (
                        {pht}.{user_id} = {users}.{id}
                        AND
                        {users}.{is_admin} = ?
                        )
                        OR (
                        {pht}.{user_id} = {users}.{parent_id}
                        OR
                        {users}.{is_admin} = ?
                        )
                    )',
            [
                true,
                false,
            ],
            $select
        );
    }

    public function testJoinOn3(): void
    {
        $select = $this->database->select()
            ->from(['users', 'admins'])
            ->join('LEFT', 'photos', 'pht', [
                'pht.user_id' => 'admins.id',
                'users.is_admin' => 'pht.is_admin',
                '@or' => [
                    [
                        'users.name' => new Parameter('Anton'),
                        'users.is_admin' => 'pht.is_admin',
                    ],
                    [
                        'users.status' => new Parameter('disabled'),
                    ],
                ],
            ]);

        $this->assertSameQueryWithParameters(
            'SELECT * FROM {users}, {admins} LEFT JOIN {photos} AS {pht}
                    ON (
                        {pht}.{user_id} = {admins}.{id}
                        AND
                        {users}.{is_admin} = {pht}.{is_admin}
                        AND
                        (
                            (
                            {users}.{name} = ?
                            AND
                            {users}.{is_admin} = {pht}.{is_admin}
                            )
                            OR
                            {users}.{status} = ?
                        )
                    )',
            [
                'Anton',
                'disabled',
            ],
            $select
        );
    }

    public function testJoinOn4(): void
    {
        $select = $this->database->select()
            ->from(['users', 'admins'])
            ->join('LEFT', 'photos', 'pht', fn ($select, string $boolean, callable $wrapper) => $select
                ->on('photos.user_id', 'users.id')
                ->onWhere('photos.type', 'avatar'));

        $this->assertSameQueryWithParameters(
            'SELECT * FROM {users}, {admins} LEFT JOIN {photos} AS {pht}
                    ON (
                        {photos}.{user_id} = {users}.{id}
                        AND
                        {photos}.{type} = ?
                        )',
            [
                'avatar',
            ],
            $select
        );
    }

    public function testJoinOn5(): void
    {
        $select = $this->database->select()
            ->from(['users', 'admins'])
            ->join('LEFT', 'photos', 'pht', [
                'pht.user_id' => 'users.id',
                'users.is_admin' => 'pht.is_admin',
                fn ($select, string $boolean, callable $wrapper) => $select
                    ->on('photos.user_id', 'users.id')
                    ->onWhere('photos.type', 'avatar'),
            ]);

        $this->assertSameQueryWithParameters(
            'SELECT * FROM {users}, {admins} LEFT JOIN {photos} AS {pht}
                    ON (
                        {pht}.{user_id} = {users}.{id}
                        AND
                        {users}.{is_admin} = {pht}.{is_admin}
                        AND (
                            {photos}.{user_id} = {users}.{id}
                            AND
                            {photos}.{type} = ?
                        )
                        )',
            [
                'avatar',
            ],
            $select
        );
    }

    public function testJoinOn6(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('LEFT', 'photos', 'pht', [['pht.user_id' => 'users.id']]);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos} AS {pht} ON {pht}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testRightJoin0(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('RIGHT', 'photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            'SELECT * FROM {users} RIGHT JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testRightJoin1(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('RIGHT', 'photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            'SELECT * FROM {users} RIGHT JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testRightJoin2(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->rightJoin('photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            'SELECT * FROM {users} RIGHT JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testRightJoin3(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->rightJoin('photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            'SELECT * FROM {users} RIGHT JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testInnerJoin0(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('INNER', 'photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            'SELECT * FROM {users} INNER JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testInnerJoin1(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('INNER', 'photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            'SELECT * FROM {users} INNER JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testInnerJoin2(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->innerJoin('photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            'SELECT * FROM {users} INNER JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testInnerJoin3(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->innerJoin('photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            'SELECT * FROM {users} INNER JOIN {photos} ON {photos}.{user_id} = {users}.{id}',
            $select
        );
    }

    public function testJoinWithFullTypeName(): void
    {
        $select = $this->database->select()
            ->from('temperature as t')
            ->where('t.date', '2022-01-05')
            ->join(
                type: 'LEFT JOIN LATERAL',
                outer: $this->database->select()
                    ->from('humidity')
                    ->where('h.date', '<=', 't.date'),
                alias: 'h',
                on: new Fragment('true')
            );

        $this->assertSameQuery(
            'SELECT * FROM {temperature} AS {t}
            LEFT JOIN LATERAL (SELECT * FROM {humidity} WHERE {h}.{date} <= ?) AS {h}
            ON true WHERE {t}.{date} = ?',
            $select
        );
    }

    //Join with WHERE

    public function testJoinOnWhereParameterOrder(): void
    {
        $select = $this->database
            ->select()
            ->columns([
                'users.id as memberId',
                'users.name as name',
                new Fragment('? as memberType', 1),
                new Fragment('? as accessType', 3),
                new Fragment('IF(admins.id, ?, ?) as invited', 1, 0),
            ])
            ->from(['users', 'admins'])
            ->leftJoin('admins', 'admins')
            ->on('users.admin_id', 'admins.id')
            ->onWhere('admins.id', 37);

        $this->assertSameQueryWithParameters(
            <<<'SQL'
                SELECT {users}.{id} AS {memberId},
                    {users}.{name} AS {name},
                    ? as memberType,
                    ? as accessType,
                    IF(admins.id, ?, ?) as invited
                FROM {users}, {admins}
                LEFT JOIN {admins} AS {admins} ON {users}.{admin_id}={admins}.{id} AND {admins}.{id} = ?
                SQL,
            [1, 3, 1, 0, 37],
            $select
        );
    }

    public function testJoinWithComplexWhere(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')->on('photos.user_id', 'users.id')->onWhere('photos.public', true);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos}
                    ON {photos}.{user_id} = {users}.{id} AND {photos}.{public} = ?',
            $select
        );
    }

    public function testJoinWithComplexOrWhere(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->orOn('photos.group_id', 'users.group_id');

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos}
                    ON {photos}.{user_id} = {users}.{id} OR {photos}.{group_id} = {users}.{group_id}',
            $select
        );
    }

    public function testJoinWithComplexAndWhere(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->andOn('photos.group_id', 'users.group_id');

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos}
                    ON {photos}.{user_id} = {users}.{id} AND {photos}.{group_id} = {users}.{group_id}',
            $select
        );
    }

    public function testJoinWithComplexAndWhereDefaults(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->on('photos.group_id', 'users.group_id');

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos}
                    ON {photos}.{user_id} = {users}.{id} AND {photos}.{group_id} = {users}.{group_id}',
            $select
        );
    }

    public function testJoinWithComplexWhereAndOR(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->onWhere('photos.public', true)
            ->orOnWhere('photos.magic', '>', 900);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos}
                    ON {photos}.{user_id} = {users}.{id} AND {photos}.{public} = ? OR {photos}.{magic} > ?',
            $select
        );
    }

    public function testJoinWithComplexWhereAnd(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->onWhere('photos.public', true)
            ->andOnWhere('photos.magic', '>', 900);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos}
                    ON {photos}.{user_id} = {users}.{id} AND {photos}.{public} = ? AND {photos}.{magic} > ?',
            $select
        );
    }

    public function testJoinWithComplexWhereAndDefaults(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->onWhere('photos.public', true)
            ->onWhere('photos.magic', '>', 900);

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos}
                    ON {photos}.{user_id} = {users}.{id} AND {photos}.{public} = ? AND {photos}.{magic} > ?',
            $select
        );
    }

    //Join aliases

    public function testJoinAliases(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos as p')
            ->on(
                [
                    'p.user_id' => 'users.id',
                    'p.public' => new Parameter(true),
                ]
            );

        $this->assertSameQuery(
            'SELECT * FROM {users} LEFT JOIN {photos} AS {p}
                    ON ({p}.{user_id} = {users}.{id} AND {p}.{public} = ?)',
            $select
        );
    }

    public function testJoinAliasesWithPrefixes(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->leftJoin('photos as p')
            ->on(
                [
                    'p.user_id' => 'users.id',
                    'p.public' => new Parameter(true),
                ]
            );

        $this->assertSameQuery(
            'SELECT * FROM {prefix_users} LEFT JOIN {prefix_photos} AS {p}
                    ON ({p}.{user_id} = {prefix_users}.{id} AND {p}.{public} = ?)',
            $select
        );
    }

    public function testJoinAliasesWithPrefixesAlternative(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->leftJoin('photos', 'p')
            ->on(
                [
                    'p.user_id' => 'users.id',
                    'p.public' => new Parameter(true),
                ]
            );

        $this->assertSameQuery(
            'SELECT * FROM {prefix_users} LEFT JOIN {prefix_photos} AS {p}
                    ON ({p}.{user_id} = {prefix_users}.{id} AND {p}.{public} = ?)',
            $select
        );
    }

    public function testJoinAliasesWithPrefixesAndAliases(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users as u'])
            ->leftJoin('photos as p')
            ->on(
                [
                    'p.user_id' => 'u.id',
                    'p.public' => new Parameter(true),
                ]
            );

        $this->assertSameQuery(
            'SELECT * FROM {prefix_users} AS {u} LEFT JOIN {prefix_photos} AS {p}
                    ON ({p}.{user_id} = {u}.{id} AND {p}.{public} = ?)',
            $select
        );
    }

    //Complex verification example
    public function testComplexExample(): void
    {
        $statuses = new Parameter(['active', 'disabled']);

        $select = $this->db('prefixed', 'prefix_')
            ->select('COUNT(*)', 'groups.id', 'u.id', 'SUM(t.amount)')
            ->from(['users as u'])
            ->leftJoin('transactions as t')->on(['t.user_id' => 'u.id'])
            ->rightJoin('groups')->on(['groups.id' => 'u.group_id'])
            ->onWhere('groups.public', true)
            ->where('u.status', 'IN', $statuses)
            ->orderBy('u.name', 'DESC')
            ->groupBy('u.id');

        $this->assertSameQuery(
            'SELECT COUNT(*), {prefix_groups}.{id}, {u}.{id}, SUM({t}.{amount}) '
            . 'FROM {prefix_users} AS {u}'
            . 'LEFT JOIN {prefix_transactions} AS {t} ON {t}.{user_id} = {u}.{id}'
            . 'RIGHT JOIN {prefix_groups} ON {prefix_groups}.{id} = {u}.{group_id} AND {prefix_groups}.{public} = ?'
            . 'WHERE {u}.{status} IN (?,?)'
            . 'GROUP BY {u}.{id}'
            . 'ORDER BY {u}.{name} DESC',
            $select
        );
    }

    public function testJoinQuery(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users as u'])
            ->leftJoin(
                $this->db('prefixed', 'prefix_')
                    ->select()->from('posts AS p')
                    ->where('p.user_id', new Expression('u.id')),
                'sub_posts'
            );

        $this->assertSameQuery(
            'SELECT * FROM {prefix_users} AS {u} LEFT JOIN (
                    SELECT * FROM {prefix_posts} AS {p}
                    WHERE {p}.{user_id} = {u}.{id}
                  ) AS {sub_posts} ',
            $select
        );
    }

    public function testJoinQueryWithParameters(): void
    {
        $subSelect = $this->db('prefixed', 'prefix_')->select()
            ->from('posts AS p')
            ->where('p.feed', 'news');

        $select = $this->db('prefixed', 'prefix_')->select()
            ->columns('u.id, u.name, p.feed, p.title')
            ->from(['users as u'])
            ->innerJoin($subSelect, 'p')
            ->on('p.user_id', 'u.id')
            ->onWhere('p.channel', 'internal')
            ->where('u.status', 'active');

        $this->assertSameQueryWithParameters(
            'SELECT {u}.{id}, {u}.{name}, {p}.{feed}, {p}.{title}
                FROM {prefix_users} AS {u}
                INNER JOIN (
                  SELECT *
                  FROM {prefix_posts} AS {p}
                  WHERE {p}.{feed} = ?
                ) AS {p} ON {p}.{user_id} = {u}.{id} AND {p}.{channel} = ?
              WHERE {u}.{status} = ?',
            ['news', 'internal', 'active'],
            $select,
        );
    }
}
