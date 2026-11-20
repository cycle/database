<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Config\SQLServer\TcpConnectionConfig;
use Cycle\Database\Config\SQLServerDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\Driver\Compiler;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Driver\SQLServer\SQLServerDriver;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\QueryInterface;
use Cycle\Database\Query\QueryParameters;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

/**
 * Exact SQL, whitespace included, from a driver without the query cache, so the parameters come
 * from the compiler itself.
 */
#[Test]
final class CompilerTest
{
    public function testCompileJsonOrderByShouldReturnOriginalStatement(): void
    {
        $compiler = new class extends Compiler {
            protected function limit(QueryParameters $params, Quoter $q, ?int $limit = null, ?int $offset = null): string {}
        };

        $ref = new \ReflectionMethod($compiler, 'compileJsonOrderBy');
        $ref->setAccessible(true);

        Assert::same($ref->invoke($compiler, 'foo-bar'), 'foo-bar');
    }

    public function queries(): iterable
    {
        yield 'insert into a prefixed table' => [
            static fn(Database $db): QueryInterface => $db->insert('t')->values(['a' => 1]),
            'INSERT INTO "p_t" ("a") VALUES (?)',
            [1],
        ];
        yield 'insert of default values into a prefixed table' => [
            static fn(Database $db): QueryInterface => $db->insert('t')->values([]),
            'INSERT INTO "p_t" DEFAULT VALUES',
            [],
        ];
        yield 'upsert into a prefixed table' => [
            static fn(Database $db): QueryInterface => $db->insert('t')->values(['a' => 1, 'b' => 2])->onConflict('a'),
            'INSERT INTO "p_t" ("a", "b") VALUES (?, ?) ON CONFLICT ("a") DO UPDATE SET "b" = EXCLUDED."b"',
            [1, 2],
        ];
        yield 'update of a prefixed table' => [
            static fn(Database $db): QueryInterface => $db->update('t', ['a' => 1], ['b' => 2]),
            "UPDATE \"p_t\"\nSET \"a\" = ?\nWHERE \"b\" = ? ",
            [1, 2],
        ];
        yield 'delete from a prefixed table' => [
            static fn(Database $db): QueryInterface => $db->delete('t', ['b' => 2]),
            "DELETE FROM \"p_t\"\nWHERE \"b\" = ? ",
            [2],
        ];
        yield 'two unions' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->union($db->select()->from('u'))
                ->unionAll($db->select()->from('v')),
            "SELECT *\nFROM \"p_t\"\nUNION \n(SELECT *\nFROM \"p_u\")\nUNION ALL\n(SELECT *\nFROM \"p_v\")",
            [],
        ];
        yield 'two intersects' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->intersect($db->select()->from('u'))
                ->intersectAll($db->select()->from('v')),
            "SELECT *\nFROM \"p_t\"\nINTERSECT \n(SELECT *\nFROM \"p_u\")\nINTERSECT ALL\n(SELECT *\nFROM \"p_v\")",
            [],
        ];
        yield 'two excepts' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->except($db->select()->from('u'))
                ->exceptAll($db->select()->from('v')),
            "SELECT *\nFROM \"p_t\"\nEXCEPT \n(SELECT *\nFROM \"p_u\")\nEXCEPT ALL\n(SELECT *\nFROM \"p_v\")",
            [],
        ];
        yield 'order by a lowercase direction' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')->orderBy('a', 'desc')->orderBy('b'),
            "SELECT *\nFROM \"p_t\"\nORDER BY \"a\" DESC, \"b\" ASC",
            [],
        ];
        yield 'fragment and between conditions' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->where('a', 1)
                ->orWhere(new Fragment('b = ?', 2))
                ->where('c', 'between', 3, 4),
            "SELECT *\nFROM \"p_t\"\nWHERE \"a\" = ? OR b = ? AND \"c\" BETWEEN ? AND ? ",
            [1, 2, 3, 4],
        ];
        yield 'negated first condition' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')->whereNot('a', 1),
            "SELECT *\nFROM \"p_t\"\nWHERE NOT \"a\" = ? ",
            [1],
        ];
        yield 'lowercase in with a fragment among the values' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->where('a', 'in', new Parameter([1, new Fragment('x'), 2])),
            "SELECT *\nFROM \"p_t\"\nWHERE \"a\" IN(?,x,?) ",
            [1, 2],
        ];
        yield 'not in' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')->where('a', 'not in', new Parameter([3])),
            "SELECT *\nFROM \"p_t\"\nWHERE \"a\" NOT IN(?) ",
            [3],
        ];
        yield 'in with fragments only' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->where('a', 'in', new Parameter([new Fragment('x')])),
            "SELECT *\nFROM \"p_t\"\nWHERE \"a\" IN(x) ",
            [],
        ];
        yield 'expression on the right' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')->where('a', '>', new Expression('b')),
            "SELECT *\nFROM \"p_t\"\nWHERE \"a\" > \"b\" ",
            [],
        ];
        yield 'group of conditions' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->where(static fn($q) => $q->where('a', 1)->orWhere('b', 2))
                ->where('c', 3),
            "SELECT *\nFROM \"p_t\"\nWHERE (\"a\" = ? OR \"b\" = ?  )AND \"c\" = ? ",
            [1, 2, 3],
        ];
        yield 'group by and having' => [
            static fn(Database $db): QueryInterface => $db->select('a')->from('t')->groupBy('a')->having('a', '>', 1),
            "SELECT \"a\"\nFROM \"p_t\"\nGROUP BY \"a\" \nHAVING \"a\" > ? ",
            [1],
        ];
        yield 'join' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->join('LEFT', 'u')->on('u.id', 't.id')
                ->where('x', 1),
            "SELECT *\nFROM \"p_t\" \nLEFT JOIN \"p_u\"\n    ON \"p_u\".\"id\" = \"p_t\".\"id\"  \nWHERE \"x\" = ? ",
            [1],
        ];
        yield 'between in an array condition' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')->where(['a' => ['between' => [1, 2]]]),
            "SELECT *\nFROM \"p_t\"\nWHERE \"a\" BETWEEN ? AND ? ",
            [1, 2],
        ];
        yield 'closure followed by a column in an array condition' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->where([static fn($q) => $q->where('a', 1), 'b' => 2]),
            "SELECT *\nFROM \"p_t\"\nWHERE ((\"a\" = ?  )AND \"b\" = ?  )",
            [1, 2],
        ];
        yield 'or group followed by a column in an array condition' => [
            static fn(Database $db): QueryInterface => $db->select()->from('t')
                ->where(['@or' => [['a' => 1], ['b' => 2]], 'c' => 3]),
            "SELECT *\nFROM \"p_t\"\nWHERE ((\"a\" = ? OR \"b\" = ?  )AND \"c\" = ?  )",
            [1, 2, 3],
        ];
    }

    public function rejectsASecondValueForAnOperatorOtherThanBetween(): never
    {
        $db = new Database('default', '', SQLiteDriver::create(new SQLiteDriverConfig()));

        Expect::exception(BuilderException::class)
            ->withMessage('Only "BETWEEN" or "NOT BETWEEN" can define second comparision value');

        $db->select()->from('t')->where('a', '>', 1, 2);
    }

    #[DataProvider('queries')]
    public function compilesExactSql(\Closure $build, string $sql, array $parameters): void
    {
        $db = new Database('default', 'p_', SQLiteDriver::create(new SQLiteDriverConfig(queryCache: false)));

        $this->assertCompiles($build($db), $sql, $parameters);
    }

    public function compilesAnUpsertAsAMergeWithQualifiedTargetColumns(): void
    {
        $driver = SQLServerDriver::create(new SQLServerDriverConfig(new TcpConnectionConfig('db'), queryCache: false));
        $db = new Database('default', '', $driver);

        $this->assertCompiles(
            $db->insert('t')->values(['a' => 1, 'b' => 2])->onConflict('a'),
            'MERGE INTO [t] WITH (HOLDLOCK) AS [target] USING (VALUES (?, ?)) AS [source] ([a], [b]) '
            . 'ON [target].[a] = [source].[a] WHEN MATCHED THEN UPDATE SET [target].[b] = [source].[b] '
            . 'WHEN NOT MATCHED THEN INSERT ([a], [b]) VALUES ([source].[a], [source].[b]);',
            [1, 2],
        );
    }

    public function refusesAFragmentOfAnUnknownType(): never
    {
        Expect::exception(CompilerException::class)->withMessage('Unknown fragment type 999');

        (new SQLiteCompiler())->compile(new QueryParameters(), '', self::fragment(999, []));
    }

    public function refusesAnUpsertWithoutConflictState(): never
    {
        $tokens = ['table' => 't', 'columns' => ['a'], 'values' => [new Parameter([1])], 'onConflict' => null];

        Expect::exception(CompilerException::class)->withMessage('Upsert query requires onConflict state to be configured.');

        (new SQLiteCompiler())->compile(new QueryParameters(), '', self::fragment(CompilerInterface::UPSERT_QUERY, $tokens));
    }

    public function refusesAConditionValueThatIsNeitherAParameterNorAFragment(): never
    {
        $db = new Database('default', '', SQLiteDriver::create(new SQLiteDriverConfig(queryCache: false)));
        $tokens = $db->select()->from('t')->where('a', 1)->getTokens();
        $tokens['where'][0][1][2] = 1;

        Expect::exception(CompilerException::class)->withMessage('Invalid value format, fragment or parameter is expected');

        (new SQLiteCompiler())->compile(new QueryParameters(), '', self::fragment(CompilerInterface::SELECT_QUERY, $tokens));
    }

    public function rendersTheConflictTargetAsAColumnListByDefault(): void
    {
        $compiler = new class extends Compiler {
            protected function limit(QueryParameters $params, Quoter $q, ?int $limit = null, ?int $offset = null): string {}
        };
        $db = new Database('default', '', SQLiteDriver::create(new SQLiteDriverConfig()));

        $sql = $compiler->compile(new QueryParameters(), '', $db->insert('t')->values(['a' => 1, 'b' => 2])->onConflict('a'));

        Assert::same($sql, 'INSERT INTO "t" ("a", "b") VALUES (?, ?) ON CONFLICT ("a") DO UPDATE SET "b" = EXCLUDED."b"');
    }

    private static function fragment(int $type, array $tokens): FragmentInterface
    {
        return new class($type, $tokens) implements FragmentInterface {
            public function __construct(
                private readonly int $type,
                private readonly array $tokens,
            ) {}

            public function getType(): int
            {
                return $this->type;
            }

            public function getTokens(): array
            {
                return $this->tokens;
            }
        };
    }

    private function assertCompiles(QueryInterface $query, string $sql, array $parameters): void
    {
        $params = new QueryParameters();

        Assert::same($query->sqlStatement($params), $sql);
        Assert::same(
            \array_map(static fn(mixed $parameter): mixed => $parameter instanceof Parameter ? $parameter->getValue() : $parameter, $params->getParameters()),
            $parameters,
        );
    }
}
