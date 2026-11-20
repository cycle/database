<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\Driver\CompilerCache;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\Postgres\PostgresCompiler;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Injection\SubQuery;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Query\QueryInterface;
use Cycle\Database\Query\QueryParameters;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

/**
 * The cache keys a compiled query by a hash of its tokens: two queries that compile differently must
 * never share a hash, or the second one runs the SQL of the first.
 */
#[Test]
final class CompilerCacheTest
{
    public function testHashInsertQueryWithReturningFragment(): void
    {
        $compiler = new CompilerCache(new PostgresCompiler());
        $ref = new \ReflectionMethod($compiler, 'hashInsertQuery');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($compiler, new QueryParameters(), [
            'table' => 'some_table',
            'columns' => ['name', 'full_name'],
            'values' => ['Foo'],
            'return' => ['name', new Fragment('"full_name" as "fullName"')],
        ]), 'i_some_tablename_full_name_rname_"full_name" as "fullName"P?');
    }

    public function sqliteSequences(): iterable
    {
        yield 'insert into the same table under another prefix' => [
            static fn(\Closure $db): array => [
                $db('a_')->insert('t')->values(['x' => 1]),
                $db('b_')->insert('t')->values(['x' => 1]),
            ],
        ];
        yield 'upsert into the same table under another prefix' => [
            static fn(\Closure $db): array => [
                $db('a_')->insert('t')->values(['x' => 1])->onConflict('x'),
                $db('b_')->insert('t')->values(['x' => 1])->onConflict('x'),
            ],
        ];
        yield 'upsert on another target' => [
            static fn(\Closure $db): array => [
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict('x'),
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict('y'),
            ],
        ];
        yield 'upsert updating listed columns on another target' => [
            static fn(\Closure $db): array => [
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict(OnConflict::target('x')->doUpdate(['y'])),
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict(OnConflict::target('y')->doUpdate(['y'])),
            ],
        ];
        yield 'upsert updating mapped columns on another target' => [
            static fn(\Closure $db): array => [
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict(OnConflict::target('x')->doUpdate(['y' => 3])),
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict(OnConflict::target('y')->doUpdate(['y' => 3])),
            ],
        ];
        yield 'upsert updating other mapped columns' => [
            static fn(\Closure $db): array => [
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict(OnConflict::target('x')->doUpdate(['y' => 3, 'z' => 4])),
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict(OnConflict::target('x')->doUpdate(['w' => 3, 'z' => 4])),
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict(OnConflict::target('x')->doUpdate(['w' => 3])),
                $db('')->insert('t')->values(['x' => 1, 'y' => 2])->onConflict(OnConflict::target('x')->doUpdate(['v' => 3])),
            ],
        ];
        yield 'upsert updating a fragment followed by another column' => [
            static fn(\Closure $db): array => [
                $db('')->insert('t')->values(['x' => 1])->onConflict(OnConflict::target('x')->doUpdate(['y' => new Fragment('?', 3), 'z' => 4])),
                $db('')->insert('t')->values(['x' => 1])->onConflict(OnConflict::target('x')->doUpdate(['y' => new Fragment('?', 3), 'w' => 4])),
            ],
        ];
    }

    public function postgresSequences(): iterable
    {
        yield 'distinct on another column' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->distinctOn('a'),
                $db('')->select()->from('t')->distinctOn('b'),
            ],
        ];
        yield 'for update' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t'),
                $db('')->select()->from('t')->forUpdate(),
            ],
        ];
        yield 'nested select from another prefix' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from($db('a_')->select()->from('t')),
                $db('')->select()->from($db('b_')->select()->from('t')),
            ],
        ];
        yield 'nested select after a distinct' => [
            static fn(\Closure $db): array => [
                $db('')->select()->distinct()->from($db('a_')->select()->from('t')),
                $db('')->select()->from($db('a_')->select()->from('t')),
            ],
        ];
        yield 'nested select followed by another table' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from([$db('a_')->select()->from('t'), 'x']),
                $db('')->select()->from([$db('a_')->select()->from('t'), 'y']),
            ],
        ];
        yield 'subquery after a distinct' => [
            static fn(\Closure $db): array => [
                $db('')->select()->distinct()->from(new SubQuery($db('')->select()->from('t'), 's')),
                $db('')->select()->from(new SubQuery($db('')->select()->from('t'), 's')),
            ],
        ];
        yield 'join under another alias' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->join('LEFT', 'u', 'a')->on('a.id', 't.id'),
                $db('')->select()->from('t')->join('LEFT', 'u', 'b')->on('a.id', 't.id'),
            ],
        ];
        yield 'join from another table' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t1')->join('LEFT', 'u', 'a')->on('a.id', 'b.id'),
                $db('')->select()->from('t2')->join('LEFT', 'u', 'a')->on('a.id', 'b.id'),
            ],
        ];
        yield 'joined select from another prefix' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->join('LEFT', $db('a_')->select()->from('u'), 's')->on('s.id', 't.id'),
                $db('')->select()->from('t')->join('LEFT', $db('b_')->select()->from('u'), 's')->on('s.id', 't.id'),
            ],
        ];
        yield 'joined select from another table' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t1')->join('LEFT', $db('a_')->select()->from('u'), 's')->on('s.id', 'b.id'),
                $db('')->select()->from('t2')->join('LEFT', $db('a_')->select()->from('u'), 's')->on('s.id', 'b.id'),
            ],
        ];
        yield 'union with another prefix' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->union($db('a_')->select()->from('u')),
                $db('')->select()->from('t')->union($db('b_')->select()->from('u')),
            ],
        ];
        yield 'union from another table' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t1')->union($db('a_')->select()->from('u')),
                $db('')->select()->from('t2')->union($db('a_')->select()->from('u')),
            ],
        ];
        yield 'intersect with another prefix' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->intersect($db('a_')->select()->from('u')),
                $db('')->select()->from('t')->intersect($db('b_')->select()->from('u')),
            ],
        ];
        yield 'intersect from another table' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t1')->intersect($db('a_')->select()->from('u')),
                $db('')->select()->from('t2')->intersect($db('a_')->select()->from('u')),
            ],
        ];
        yield 'except with another prefix' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->except($db('a_')->select()->from('u')),
                $db('')->select()->from('t')->except($db('b_')->select()->from('u')),
            ],
        ];
        yield 'except from another table' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t1')->except($db('a_')->select()->from('u')),
                $db('')->select()->from('t2')->except($db('a_')->select()->from('u')),
            ],
        ];
        yield 'fragment condition followed by another condition' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->where(new Fragment('a = ?', 1))->where('b', 2),
                $db('')->select()->from('t')->where(new Fragment('a = ?', 1))->where('c', 2),
            ],
        ];
        yield 'subquery on the left of a condition from another prefix' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->where('x', 1)->where($db('a_')->select('id')->from('u')->limit(1), 1),
                $db('')->select()->from('t')->where('x', 1)->where($db('b_')->select('id')->from('u')->limit(1), 1),
            ],
        ];
        yield 'subquery on the left of a condition after another condition' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->where('x', 1)->where($db('a_')->select('id')->from('u')->limit(1), 1),
                $db('')->select()->from('t')->where('y', 1)->where($db('a_')->select('id')->from('u')->limit(1), 1),
            ],
        ];
        yield 'parameter on the left of a condition after another condition' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->where('x', 1)->where(new Parameter(1), 2),
                $db('')->select()->from('t')->where('y', 1)->where(new Parameter(1), 2),
            ],
        ];
        yield 'subquery on the right of a condition from another prefix' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->where('id', 'IN', $db('a_')->select('id')->from('u')),
                $db('')->select()->from('t')->where('id', 'IN', $db('b_')->select('id')->from('u')),
            ],
        ];
        yield 'between two parameters' => [
            static fn(\Closure $db): array => [
                $db('')->select()->from('t')->where('a', 'between', 1, 2),
            ],
        ];
        yield 'columns joined differently' => [
            static fn(\Closure $db): array => [
                $db('')->select(['ab'])->from('t'),
                $db('')->select(['a', 'b'])->from('t'),
            ],
        ];
        yield 'columns ending the same' => [
            static fn(\Closure $db): array => [
                $db('')->select(['a', 'b'])->from('t'),
                $db('')->select(['c', 'b'])->from('t'),
            ],
        ];
    }

    #[DataProvider('sqliteSequences')]
    public function compilesSQLiteQueriesLikeTheUncachedCompiler(\Closure $build): void
    {
        $this->assertSameAsUncached(
            SQLiteDriver::create(new SQLiteDriverConfig(queryCache: true)),
            SQLiteDriver::create(new SQLiteDriverConfig(queryCache: false)),
            $build,
        );
    }

    #[DataProvider('postgresSequences')]
    public function compilesPostgresQueriesLikeTheUncachedCompiler(\Closure $build): void
    {
        $config = static fn(bool $cache): PostgresDriverConfig => new PostgresDriverConfig(
            new TcpConnectionConfig('db'),
            queryCache: $cache,
        );

        $this->assertSameAsUncached(
            PostgresDriver::create($config(true)),
            PostgresDriver::create($config(false)),
            $build,
        );
    }

    public function compilesARepeatedQueryOnce(): void
    {
        $compiler = new CountingSQLiteCompiler();
        $cache = new CompilerCache($compiler);
        $db = new Database('default', '', SQLiteDriver::create(new SQLiteDriverConfig()));
        $queries = [
            $db->select()->from('t')->where('x', 1),
            $db->insert('t')->values(['x' => 1]),
            $db->insert('t')->values(['x' => 1])->onConflict('x'),
        ];

        foreach ($queries as $query) {
            $cache->compile(new QueryParameters(), '', $query);
            $cache->compile(new QueryParameters(), '', $query);
        }

        Assert::same($compiler->calls, 3);
    }

    public function compilesAMultiRowInsertEveryTime(): void
    {
        $compiler = new CountingSQLiteCompiler();
        $cache = new CompilerCache($compiler);
        $db = new Database('default', '', SQLiteDriver::create(new SQLiteDriverConfig()));
        $insert = $db->insert('t')->columns('x')->values([1])->values([2]);

        $cache->compile(new QueryParameters(), '', $insert);
        $cache->compile(new QueryParameters(), '', $insert);

        Assert::same($compiler->calls, 2);
    }

    /**
     * @return array{string, list<mixed>}
     */
    private static function compile(QueryInterface $query): array
    {
        $params = new QueryParameters();
        $sql = $query->sqlStatement($params);

        return [$sql, \array_map(self::value(...), $params->getParameters())];
    }

    private static function value(mixed $parameter): mixed
    {
        $value = $parameter instanceof Parameter ? $parameter->getValue() : $parameter;

        return \is_array($value)
            ? \array_map(static fn(mixed $item): mixed => $item instanceof FragmentInterface ? (string) $item : $item, $value)
            : $value;
    }

    /**
     * Compiles the built queries in order through one cached driver and checks each against the
     * same query compiled without the cache.
     */
    private function assertSameAsUncached(DriverInterface $cached, DriverInterface $plain, \Closure $build): void
    {
        $cachedQueries = $build(static fn(string $prefix): Database => new Database('default', $prefix, $cached));
        $plainQueries = $build(static fn(string $prefix): Database => new Database('default', $prefix, $plain));

        foreach ($cachedQueries as $i => $query) {
            Assert::same(self::compile($query), self::compile($plainQueries[$i]));
        }
    }
}

final class CountingSQLiteCompiler extends SQLiteCompiler
{
    public int $calls = 0;

    public function compile(QueryParameters $params, string $prefix, FragmentInterface $fragment): string
    {
        ++$this->calls;

        return parent::compile($params, $prefix, $fragment);
    }
}
