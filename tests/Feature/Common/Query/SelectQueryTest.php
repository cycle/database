<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Query;

use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Exception\CompilerException\UnexpectedOperatorException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Injection\SubQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Testing\Database\Assert\SqlAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Spiral\Pagination\PaginableInterface;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Expect;
use Testo\Test;

abstract class SelectQueryTest extends DatabaseTestCase
{
    #[Test]
    public function testQueryInstance(): void
    {
        Assert::instanceOf($this->database->select(), SelectQuery::class);
        Assert::instanceOf($this->database->table('table')->select(), SelectQuery::class);
        Assert::instanceOf($this->database->table->select(), SelectQuery::class);
        Assert::instanceOf($this->database->table->select(), \IteratorAggregate::class);
        Assert::instanceOf($this->database->table->select(), PaginableInterface::class);
    }

    #[Test]
    public function testCompileQuery(): void
    {
        $select = $this->db()
            ->select('*')
            ->from('table', 'table2')
            ->where(['name' => 'Antony']);

        SqlAssert::same((string) $select, 'SELECT * FROM {table}, {table2} WHERE {name} = \'Antony\'');

        Assert::array($select->__debugInfo());
    }

    #[Test]
    public function testCompileQueryExtraParam(): void
    {
        $select = $this->db()
            ->select('*')
            ->from('table')
            ->where(new Parameter('string'), 'string');

        SqlAssert::same((string) $select, 'SELECT * FROM {table} WHERE \'string\' = \'string\'');

        SqlAssert::sameParameters($select, ['string', 'string']);
    }

    #[Test]
    public function testSimpleWhere(): void
    {
        $select = $this->database->select()->from('table')->where('id', 1);

        SqlAssert::same($select, 'SELECT * FROM {table} WHERE {id} = ?');
        SqlAssert::sameParameters($select, [
            1,
        ]);
    }

    #[Test]
    public function testArrayWhere(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 'IN', new Parameter([1, 2, 3, 4]));

        SqlAssert::same($select, 'SELECT * FROM {table} WHERE {id} IN (?, ?, ?, ?)');
        SqlAssert::sameParameters($select, [1, 2, 3, 4]);
    }

    #[Test]
    public function testCompileNestedQuery(): void
    {
        $select = $this->db()
            ->select('*')
            ->from('table', 'table2')
            ->where(['name' => 'Antony'])
            ->where(
                'id',
                'in',
                (new SelectQuery())
                    ->from('other')->columns('id')
                    ->where('x', 123),
            );

        SqlAssert::same((string) $select, 'SELECT * FROM {table}, {table2}
WHERE {name} = \'Antony\' AND {id} IN (SELECT{id}FROM {other}WHERE {x} = 123)');

        SqlAssert::sameParameters($select, [
            'Antony',
            123,
        ]);
    }

    #[Test]
    public function testSelectFromSelect(): void
    {
        $select = $this->db()
            ->select('id')
            ->from((new SelectQuery())->from('users')->where('id', '>', 100))
            ->where(['name' => 'Antony']);

        SqlAssert::same((string) $select, 'SELECT {id} FROM (SELECT * FROM {users} WHERE {id} > 100) WHERE {name} = \'Antony\'');

        SqlAssert::sameParameters($select, [
            100,
            'Antony',
        ]);
    }

    #[Test]
    public function testSimpleSelection(): void
    {
        $select = $this->database->select()->from('table');
        Assert::same($select->getDriver(), $this->database->getDriver());

        Assert::same($select->getTables(), ['table']);
        Assert::same($select->getColumns(), ['*']);

        SqlAssert::same($select, 'SELECT * FROM {table}');
    }

    #[Test]
    public function testMultipleTablesSelection(): void
    {
        $select = $this->database->select()->from(['tableA', 'tableB']);

        SqlAssert::same($select, 'SELECT * FROM {tableA}, {tableB}');
    }

    #[Test]
    public function testSelectDistinct(): void
    {
        $select = $this->database->select()->distinct()->from(['table']);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {table}');
    }

    #[Test]
    public function testSelectWithSimpleWhere(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('name', 'Anton');

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testSelectWithSimpleWhereNull(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('name', null);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {name} IS NULL');
    }

    #[Test]
    public function testDeadWhere(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where(
                static function (): void {},
            );

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users}');
    }

    #[Test]
    public function testSelectWithSimpleWhereNotNull(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('name', '!=', null);
        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {name} IS NOT NULL');

        $select = $this->database->select()->distinct()->from(['users'])->whereNot('name', null);
        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE NOT {name} IS NULL');
    }

    #[Test]
    public function testSelectWithWhereWithOperator(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->where('name', 'LIKE', 'Anton%');

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {name} LIKE ?');
    }

    #[Test]
    public function testSelectWithWhereWithBetween(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->where('balance', 'BETWEEN', 0, 1000);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {balance} BETWEEN ? AND ?');
    }

    #[Test]
    public function testSelectWithWhereWithNotBetween(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('balance', 'NOT BETWEEN', 0, 1000);
        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {balance} NOT BETWEEN ? AND ?');

        $select = $this->database->select()->distinct()->from(['users'])->whereNot('balance', 'BETWEEN', 0, 1000);
        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE NOT {balance} BETWEEN ? AND ?');
    }

    #[Test]
    public function testSelectWithWhereBetweenBadValue(): void
    {
        Expect::exception(BuilderException::class)->withMessageContaining('Between statements expects exactly 2 values');

        $this->database->select()->distinct()->from(['users'])->where('balance', 'BETWEEN', 0);
    }

    #[Test]
    public function testSelectWithFullySpecificColumnNameInWhere(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->where('users.balance', 12);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {users}.{balance} = ?');
    }

    #[Test]
    public function testPrefixedSelectWithFullySpecificColumnNameInWhere(): void
    {
        $select = $this->db('prefixed', 'prefix_')
            ->select()
            ->distinct()
            ->from(['users'])
            ->where('users.balance', 12);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {prefix_users} WHERE {prefix_users}.{balance} = ?');
    }

    #[Test]
    public function testPrefixedSelectWithFullySpecificColumnNameInWhereButAliased(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()->distinct()->from(['users as u'])
            ->where('u.balance', 12);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {prefix_users} AS {u} WHERE {u}.{balance} = ?');
    }

    //Simple combinations testing

    #[Test]
    public function testSelectWithWhereAndWhere(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->andWhere('balance', '>', 1);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {name} = ? AND {balance} > ?');
    }

    #[Test]
    public function testSelectWithWhereAndFallbackWhere(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->where('balance', '>', 1);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {name} = ? AND {balance} > ?');
    }

    #[Test]
    public function testSelectWithWhereOrWhere(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere('balance', '>', 1);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {name} = ? OR {balance} > ?');
    }

    #[Test]
    #[ExpectException(UnexpectedOperatorException::class)]
    public function testSelectInvalidArrayArgument(): void
    {
        $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere('id', 'like', [1, 2, 3])
            ->sqlStatement();
    }

    #[Test]
    public function testSelectWithWhereOrWhereAndWhere(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere('balance', '>', 1)
            ->andWhere('value', 'IN', new Parameter([10, 12]));

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE {name} = ? OR {balance} > ? AND {value} IN (?, ?)');
    }

    // Combinations thought closures

    #[Test]
    public function testWhereOfOrWhere(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->andWhere(
                static function (SelectQuery $select): void {
                    $select->orWhere('value', '>', 10)
                        ->orWhere('value', '<', 1000);
                },
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND ({value} > ? OR {value} < ?)');

        SqlAssert::sameParameters($select, [
            'Anton',
            10,
            1000,
        ]);
    }

    #[Test]
    public function testWhereOfAndWhere(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->andWhere(
                static function (SelectQuery $select): void {
                    $select->where('value', '>', 10)
                        ->andWhere('value', '<', 1000);
                },
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND ({value} > ? AND {value} < ?)');
    }

    #[Test]
    public function testOrWhereOfOrWhere(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere(
                static function (SelectQuery $select): void {
                    $select->orWhere('value', '>', 10)
                        ->orWhere('value', '<', 1000);
                },
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR ({value} > ? OR {value} < ?)');
    }

    #[Test]
    public function testOrWhereOfAndWhere(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere(
                static function (SelectQuery $select): void {
                    $select->where('value', '>', 10)
                        ->andWhere('value', '<', 1000);
                },
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR ({value} > ? AND {value} < ?)');
    }

    //Short where form

    #[Test]
    public function testShortWhere(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testShortWhereWithCondition(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                [
                    'name' => [
                        'like' => 'Anton',
                        '!=' => 'Antony',
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({name} LIKE ? AND {name} != ?)');
    }

    #[Test]
    public function testShortWhereWithBetweenCondition(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                [
                    'value' => [
                        'between' => [1, 2],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {value} BETWEEN ? AND ?');
    }

    #[Test]
    public function testShortWhereWithNotBetweenCondition(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                [
                    'value' => [
                        'not between' => [1, 2],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {value} NOT BETWEEN ? AND ?');
    }

    #[Test]
    public function testShortWhereWithBetweenConditionBadArguments(): void
    {
        Expect::exception(BuilderException::class)->withMessageContaining('Exactly 2 array values are required for between statement');

        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                [
                    'value' => [
                        'between' => [1],
                    ],
                ],
            );
    }

    #[Test]
    public function testShortWhereMultiple(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                [
                    'name' => 'Anton',
                    'value' => 1,
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({name} = ? AND {value} = ?)');
    }

    #[Test]
    public function testShortWhereMultipleButNotInAGroup(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->where(['value' => 1]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND {value} = ?');
    }

    #[Test]
    public function testShortWhereOrWhere(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orWhere(['value' => 1]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR {value} = ?');
    }

    #[Test]
    public function testAndShortWhereOR(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->andWhere(
                [
                    '@or' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND ({value} = ? OR {value} > ?)');
    }

    #[Test]
    public function testAndShortWhereOrNot(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->andWhere(
                [
                    '@or not' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND (NOT {value} = ? OR NOT {value} > ?)');
    }

    #[Test]
    public function testOrShortWhereOR(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orWhere(
                [
                    '@or' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR ({value} = ? OR {value} > ?)');
    }

    #[Test]
    public function testOrShortWhereOrNot(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orWhere(
                [
                    '@or not' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR (NOT {value} = ? OR NOT {value} > ?)');
    }

    #[Test]
    public function testAndShortWhereAND(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->andWhere(
                [
                    '@and' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND ({value} = ? AND {value} > ?)');
    }

    #[Test]
    public function testAndShortWhereAndNot(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->andWhere(
                [
                    '@and not' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND (NOT {value} = ? AND NOT {value} > ?)');
    }

    #[Test]
    public function testOrShortWhereAND(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orWhere(
                [
                    '@and' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR ({value} = ? AND {value} > ?)');
    }

    #[Test]
    public function testOrShortWhereAndNot(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orWhere(
                [
                    '@and not' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR (NOT {value} = ? AND NOT {value} > ?)');
    }

    #[Test]
    public function testBadShortExpression(): void
    {
        Expect::exception(BuilderException::class)->withMessageContaining('Nested conditions should have defined operator');

        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                [
                    'status' => ['active', 'blocked'],
                ],
            );
    }

    //Order By

    #[Test]
    public function testOrderByAsc(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} ASC');
    }

    #[Test]
    public function testOrderByAsc2(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', SelectQuery::SORT_ASC);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} ASC');
    }

    #[Test]
    public function testOrderByAsc3(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', 'ASC');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} ASC');
    }

    #[Test]
    public function testOrderByDesc(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', SelectQuery::SORT_DESC);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} DESC');
    }

    #[Test]
    public function testOrderByDesc3(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', 'DESC');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} DESC');
    }

    #[Test]
    public function testOrderByTwiceBySameName(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', 'DESC')
            ->orderBy('name', 'ASC');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} DESC');
    }

    #[Test]
    public function testOrderByTwiceBySameNameArray(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', 'DESC')
            ->orderBy(['name' => 'ASC', 'foo' => 'DESC']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} DESC, {foo} DESC');
    }

    #[Test]
    public function testMultipleOrderBy(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('value', SelectQuery::SORT_ASC)
            ->orderBy('name', SelectQuery::SORT_DESC);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {value} ASC, {name} DESC');
    }

    #[Test]
    public function testMultipleOrderByViaArray(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy(
                [
                    'value' => SelectQuery::SORT_ASC,
                    'name' => SelectQuery::SORT_DESC,
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {value} ASC, {name} DESC');
    }

    #[Test]
    public function testMultipleOrderByFullySpecified(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('users.value', SelectQuery::SORT_ASC);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? ORDER BY {users}.{value} ASC');
    }

    #[Test]
    public function testMultipleOrderByFullySpecifiedPrefixed(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('users.value', SelectQuery::SORT_ASC);

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} WHERE {name} = ? ORDER BY {prefix_users}.{value} ASC');
    }

    #[Test]
    public function testMultipleOrderByFullySpecifiedAliasedAndPrefixed(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users as u'])
            ->where(['name' => 'Anton'])
            ->orderBy('u.value', SelectQuery::SORT_ASC);

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} AS {u} WHERE {name} = ? ORDER BY {u}.{value} ASC');
    }

    #[Test]
    public function testOrderByWithExpressionAndParameter(): void
    {
        $select = $this->database
            ->select()
            ->from('permissions')
            ->orderBy(new Expression('role = ?', '*'), 'DESC');

        SqlAssert::same($select, 'SELECT * FROM {permissions} ORDER BY {role} = ? DESC');
        SqlAssert::sameParameters($select, ['*']);
    }

    #[Test]
    public function testOrderByWithFragmentAndParameter(): void
    {
        $select = $this->database
            ->select()
            ->from('permissions')
            ->orderBy(new Fragment('"role" = ?', '*'), 'DESC');

        SqlAssert::same($select, 'SELECT * FROM {permissions} ORDER BY "role" = ? DESC');
        SqlAssert::sameParameters($select, ['*']);
    }

    #[Test]
    public function testOrderByWithFragmentAndParameterInArray(): void
    {
        $select = $this->database
            ->select()
            ->from('permissions')
            ->orderBy([
                new Fragment('"role" = ? DESC', '*'),
                'read' => 'ASC',
            ]);

        SqlAssert::same($select, 'SELECT * FROM {permissions} ORDER BY "role" = ? DESC, {read} ASC');
        SqlAssert::sameParameters($select, ['*']);
    }

    //Group By

    #[Test]
    public function testGroupBy(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->groupBy('name');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? GROUP BY {name}');
    }

    #[Test]
    public function testMultipleGroupBy(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->groupBy('name')
            ->groupBy('value');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? GROUP BY {name}, {value}');
    }

    #[Test]
    public function testMultipleGroupByFullySpecified(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->groupBy('users.value');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? GROUP BY {users}.{value}');
    }

    #[Test]
    public function testMultipleGroupByFullySpecifiedPrefixed(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->groupBy('users.value');

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} WHERE {name} = ? GROUP BY {prefix_users}.{value}');
    }

    #[Test]
    public function testMultipleGroupByFullySpecifiedAliasedAndPrefixed(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users as u'])
            ->where(['name' => 'Anton'])
            ->groupBy('u.value');

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} AS {u} WHERE {name} = ? GROUP BY {u}.{value}');
    }

    //Column Tweaking

    #[Test]
    public function testAllColumns(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testAllColumns1(): void
    {
        $select = $this->database
            ->select('*')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testAllColumns2(): void
    {
        $select = $this->database
            ->select(['*'])
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testAllColumns3(): void
    {
        $select = $this->database
            ->select([])
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testAllColumns4(): void
    {
        $select = $this->database
            ->select()
            ->columns('*')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testAllColumns5(): void
    {
        $select = $this->database
            ->select()
            ->columns('users.*')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {users}.* FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testAllColumnsWithPrefix(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->columns('users.*')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {prefix_users}.* FROM {prefix_users} WHERE {name} = ?');
    }

    #[Test]
    public function testAllColumnsWithPrefixAliased(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->columns('u.*')
            ->from(['users as u'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {u}.* FROM {prefix_users} AS {u} WHERE {name} = ?');
    }

    #[Test]
    public function testOneColumn(): void
    {
        $select = $this->database->select()
            ->columns('name')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {name} FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testOneFullySpecifiedColumn(): void
    {
        $select = $this->database->select()
            ->columns('users.name')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {users}.{name} FROM {users} WHERE {name} = ?');
    }

    #[Test]
    public function testOneFullySpecifiedColumnWithPrefix(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->columns('users.name')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {prefix_users}.{name} FROM {prefix_users} WHERE {name} = ?');
    }

    #[Test]
    public function testOneFullySpecifiedColumnWithPrefixButAliased(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->columns('u.name')
            ->from(['users as u'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {u}.{name} FROM {prefix_users} AS {u} WHERE {name} = ?');
    }

    #[Test]
    public function testColumnWithAlias(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->columns('u.name as u_name')
            ->from(['users as u'])
            ->where(['u_name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {u}.{name} AS {u_name} FROM {prefix_users} AS {u} WHERE {u_name} = ?');
    }

    #[Test]
    public function testColumnWithAliasWithoutTableAlias(): void
    {
        $select = $this->db('prefixed', 'prefix_')
            ->select()
            ->columns(['name as user_name'])
            ->from('users as u')
            ->where(['user_name' => 'John']);

        SqlAssert::same($select, 'SELECT {name} AS {user_name} FROM {prefix_users} AS {u} WHERE {user_name} = ?');
    }

    #[Test]
    public function testMultipleColumns(): void
    {
        $select = $this->database->select()
            ->columns(['name', 'value'])
            ->from(['users as u'])
            ->where(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT {name}, {value} FROM {users} AS {u} WHERE {name} = ?');
    }

    #[Test]
    public function testColumnsWithFunctions(): void
    {
        $select = $this->database->select()
            ->columns(['SUM(u.balance)', 'COUNT(*)'])
            ->from(['users as u'])
            ->where(['name' => 'Anton'])
            ->groupBy('balance');

        SqlAssert::same($select, 'SELECT SUM({u}.{balance}), COUNT(*) FROM {users} AS {u} WHERE {name} = ? GROUP BY {balance}');
    }

    //HAVING, Generic behaviours

    #[Test]
    public function testHavingSelectWithSimpleHaving(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])->having('name', 'Anton');

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {name} = ?');
    }

    #[Test]
    public function testHavingSelectWithHavingWithOperator(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('name', 'LIKE', 'Anton%');

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {name} LIKE ?');
    }

    #[Test]
    public function testHavingSelectWithHavingWithBetween(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('balance', 'BETWEEN', 0, 1000);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {balance} BETWEEN ? AND ?');
    }

    #[Test]
    public function testHavingSelectWithHavingWithNotBetween(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('balance', 'NOT BETWEEN', 0, 1000);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {balance} NOT BETWEEN ? AND ?');
    }

    #[Test]
    public function testHavingSelectWithHavingBetweenBadValue(): void
    {
        Expect::exception(BuilderException::class)->withMessageContaining('Between statements expects exactly 2 values');
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('balance', 'BETWEEN', 0);
    }

    #[Test]
    public function testHavingSelectWithFullySpecificColumnNameInHaving(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('users.balance', 12);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {users}.{balance} = ?');
    }

    #[Test]
    public function testHavingPrefixedSelectWithFullySpecificColumnNameInHaving(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()->distinct()->from(['users'])
            ->having('users.balance', 12);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {prefix_users} HAVING {prefix_users}.{balance} = ?');
    }

    #[Test]
    public function testHavingPrefixedSelectWithFullySpecificColumnNameInHavingButAliased(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()->distinct()->from(['users as u'])
            ->having('u.balance', 12);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {prefix_users} AS {u} HAVING {u}.{balance} = ?');
    }

    //HAVING, Simple combinations testing

    #[Test]
    public function testHavingSelectWithHavingAndHaving(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->having('name', 'Anton')
            ->andHaving('balance', '>', 1);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {name} = ? AND {balance} > ?');
    }

    #[Test]
    public function testHavingSelectWithHavingAndFallbackHaving(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->having('name', 'Anton')
            ->having('balance', '>', 1);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {name} = ? AND {balance} > ?');
    }

    #[Test]
    public function testHavingSelectWithHavingOrHaving(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->having('name', 'Anton')
            ->orHaving('balance', '>', 1);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {name} = ? OR {balance} > ?');
    }

    #[Test]
    public function testHavingSelectWithHavingOrHavingAndHaving(): void
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->having('name', 'Anton')
            ->orHaving('balance', '>', 1)
            ->andHaving('value', 'IN', new Parameter([10, 12]));

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} HAVING {name} = ? OR {balance} > ? AND {value} IN (?, ?)');
    }

    //HAVING, Combinations thought closures

    #[Test]
    public function testHavingHavingOfOrHaving(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->having('name', 'Anton')
            ->andHaving(
                static function (SelectQuery $select): void {
                    $select->orHaving('value', '>', 10)->orHaving('value', '<', 1000);
                },
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? AND ({value} > ? OR {value} < ?)');
    }

    #[Test]
    public function testHavingHavingOfAndHaving(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->having('name', 'Anton')
            ->andHaving(
                static function (SelectQuery $select): void {
                    $select->having('value', '>', 10)->andHaving('value', '<', 1000);
                },
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? AND ({value} > ? AND {value} < ?)');
    }

    #[Test]
    public function testHavingOrHavingOfOrHaving(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->having('name', 'Anton')
            ->orHaving(
                static function (SelectQuery $select): void {
                    $select->orHaving('value', '>', 10)->orHaving('value', '<', 1000);
                },
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? OR ({value} > ? OR {value} < ?)');
    }

    #[Test]
    public function testHavingOrHavingOfAndHaving(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->having('name', 'Anton')
            ->orHaving(
                static function (SelectQuery $select): void {
                    $select->having('value', '>', 10)
                        ->andHaving('value', '<', 1000);
                },
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? OR ({value} > ? AND {value} < ?)');
    }

    //HAVING, Short having form

    #[Test]
    public function testHavingShortHaving(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton']);

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ?');
    }

    #[Test]
    public function testHavingShortHavingWithCondition(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->having(
                [
                    'name' => [
                        'like' => 'Anton',
                        '!=' => 'Antony',
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING ({name} LIKE ? AND {name} != ?)');
    }

    #[Test]
    public function testHavingShortHavingWithBetweenCondition(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(
                [
                    'value' => [
                        'between' => [1, 2],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {value} BETWEEN ? AND ?');
    }

    #[Test]
    public function testHavingShortHavingWithNotBetweenCondition(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(
                [
                    'value' => [
                        'not between' => [1, 2],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {value} NOT BETWEEN ? AND ?');
    }

    #[Test]
    public function testHavingShortHavingWithBetweenConditionBadArguments(): void
    {
        Expect::exception(BuilderException::class)->withMessageContaining('Exactly 2 array values are required for between statement');

        $select = $this->database->select()
            ->from(['users'])
            ->having(
                [
                    'value' => [
                        'between' => [1],
                    ],
                ],
            );
    }

    #[Test]
    public function testHavingShortHavingMultiple(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(
                [
                    'name' => 'Anton',
                    'value' => 1,
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING ({name} = ? AND {value} = ?)');
    }

    #[Test]
    public function testHavingShortHavingMultipleButNotInAGroup(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->having(['value' => 1]);

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? AND {value} = ?');
    }

    #[Test]
    public function testHavingShortHavingOrHaving(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->orHaving(['value' => 1]);

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? OR {value} = ?');
    }

    #[Test]
    public function testHavingAndShortHavingOR(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->andHaving(
                [
                    '@or' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? AND ({value} = ? OR {value} > ?)');
    }

    #[Test]
    public function testHavingOrShortHavingOR(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->orHaving(
                [
                    '@or' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? OR ({value} = ? OR {value} > ?)');
    }

    #[Test]
    public function testHavingAndShortHavingAND(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->andHaving(
                [
                    '@and' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? AND ({value} = ? AND {value} > ?)');
    }

    #[Test]
    public function testHavingOrShortHavingAND(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->orHaving(
                [
                    '@and' => [
                        ['value' => 1],
                        ['value' => ['>' => 12]],
                    ],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} HAVING {name} = ? OR ({value} = ? AND {value} > ?)');
    }

    //Limit and offset, ATTENTION THIS SECTION IS DRIVER SPECIFIC!

    #[Test]
    public function testLimitNoOffset(): void
    {
        $select = $this->database->select()->from(['users'])->limit(10);

        SqlAssert::same($select, 'SELECT * FROM {users} LIMIT ?');

        SqlAssert::sameParameters($select, [
            10,
        ]);
    }

    #[Test]
    public function testLimitAndOffset(): void
    {
        $select = $this->database->select()->from(['users'])->limit(10)->offset(20);

        SqlAssert::same($select, 'SELECT * FROM {users} LIMIT ? OFFSET ?');


        SqlAssert::sameParameters($select, [
            10,
            20,
        ]);
    }

    #[Test]
    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        SqlAssert::same($select, 'SELECT * FROM {users} OFFSET ?');

        SqlAssert::sameParameters($select, [
            20,
        ]);
    }

    //Attention, this is proper way!
    #[Test]
    public function testLimitAndOffsetAndOrderBy(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->limit(10)
            ->orderBy('name')
            ->offset(20);

        SqlAssert::same($select, 'SELECT * FROM {users} ORDER BY {name} ASC LIMIT ? OFFSET ?');

        SqlAssert::sameParameters($select, [
            10,
            20,
        ]);
    }

    //Fragments

    #[Test]
    public function testColumnNameAsFragment(): void
    {
        $select = $this->database->select(new Fragment('_ROW_ID_'))->from(['users']);

        SqlAssert::same($select, 'SELECT _ROW_ID_ FROM {users}');
    }

    #[Test]
    public function testWhereValueAsFragment(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('balance', '=', new Fragment('(1 + 2) / 3'));

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {balance} = (1 + 2) / 3');
    }

    #[Test]
    public function testShortWhereValueAsFragment(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['balance' => new Fragment('(1 + 2) / 3')]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {balance} = (1 + 2) / 3');
    }

    #[Test]
    public function testWhereOperatorAsFragment(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', new Fragment('SUPERLIKE'), 'Anton');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} SUPERLIKE ?');
    }

    #[Test]
    public function testOrderByFragment(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->orderBy(new Fragment('RAND()'));

        SqlAssert::same($select, 'SELECT * FROM {users} ORDER BY RAND() ASC');
    }

    #[Test]
    public function testOrderByNullableDirection(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->orderBy(new Fragment('RAND()'), null);

        SqlAssert::same($select, 'SELECT * FROM {users} ORDER BY RAND()');
    }

    #[Test]
    #[ExpectException(CompilerException::class)]
    public function testOrderByWrongDirection(): void
    {
        $this->database->select()
            ->from(['users'])
            ->orderBy('column', 'FOO')
            ->sqlStatement();
    }

    //Please not this example
    #[Test]
    public function testGroupByFragment(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->groupBy(new Fragment('RESOLVE_USER(users.id)'));

        SqlAssert::same($select, 'SELECT * FROM {users} GROUP BY RESOLVE_USER(users.id)');
        //Note: see Expressions
    }

    //Expressions

    #[Test]
    public function testColumnNameAsExpression(): void
    {
        $select = $this->database->select(new Expression('name'))->from(['users']);

        SqlAssert::same($select, 'SELECT {name} FROM {users}');
    }

    #[Test]
    public function testColumnNameAndTableAsExpression(): void
    {
        $select = $this->database->select(new Expression('users.name'))->from(['users']);

        SqlAssert::same($select, 'SELECT {users}.{name} FROM {users}');
    }

    #[Test]
    public function testColumnNameAndTableAsExpressionPrefixed(): void
    {
        $select = $this->db('prefixed', 'prefix_')
            ->select(new Expression('users.name'))
            ->from(['users']);

        SqlAssert::same($select, 'SELECT {prefix_users}.{name} FROM {prefix_users}');
    }

    #[Test]
    public function testColumnNameAndTableAsExpressionPrefixedAliased(): void
    {
        $select = $this->db('prefixed', 'prefix_')
            ->select(new Expression('u.name'))
            ->from(['users as u']);

        SqlAssert::same($select, 'SELECT {u}.{name} FROM {prefix_users} AS {u}');
    }

    #[Test]
    public function testWhereValueAsExpression(): void
    {
        $select = $this->database->select()->from(['users'])
            ->where('balance', '>', new Expression('origin_balance'));

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {balance} > {origin_balance}');
    }

    #[Test]
    public function testWhereValueAndTableAsExpression(): void
    {
        $select = $this->database->select()->from(['users'])
            ->where('balance', '>', new Expression('users.origin_balance'));

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {balance} > {users}.{origin_balance}');
    }

    #[Test]
    public function testWhereValueAndTableAsExpressionPrefixed(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()->from(['users'])
            ->where('balance', '>', new Expression('users.origin_balance'));

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} WHERE {balance} > {prefix_users}.{origin_balance}');
    }

    #[Test]
    public function testWhereValueAndTableAsExpressionPrefixedAliased(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()->from(['users as u'])
            ->where('balance', '>', new Expression('u.origin_balance'));

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} AS {u} WHERE {balance} > {u}.{origin_balance}');
    }

    #[Test]
    public function testShortWhereValueAsExpressionPrefixed(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()->from(['users'])
            ->where(
                [
                    'balance' => ['>' => new Expression('users.origin_balance')],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} WHERE {balance} > {prefix_users}.{origin_balance}');
    }

    #[Test]
    public function testOrderByExpression(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()->from(['users'])
            ->orderBy(new Expression('users.balance'));

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} ORDER BY {prefix_users}.{balance} ASC');
    }

    #[Test]
    public function testGroupByExpression(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->groupBy(new Expression('RESOLVE_USER(users.id)'));

        SqlAssert::same($select, 'SELECT * FROM {users} GROUP BY RESOLVE_USER({users}.{id})');
    }

    #[Test]
    public function testGroupByExpressionWithPrefix(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->groupBy(new Expression('RESOLVE_USER(users.id)'));

        SqlAssert::same($select, 'SELECT * FROM {prefix_users} GROUP BY RESOLVE_USER({prefix_users}.{id})');
    }

    //Parameters (writing only)
    #[Test]
    public function testWhereValueAsParameter(): void
    {
        $p = new Parameter(12);

        $select = $this->database->select()
            ->from(['users'])
            ->where('balance', $p);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {balance} = ?');
    }

    #[Test]
    public function testShortWhereValueAsParameter(): void
    {
        $p = new Parameter(12);

        $select = $this->database->select()
            ->from(['users'])
            ->where(['balance' => $p]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {balance} = ?');
    }

    #[Test]
    public function testInOperatorWithArrayParameter(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('status', 'IN', ['active', 'blocked'])
            ->andWhere('age', 'not in', [1, 2, 3]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {status} IN (?, ?) AND {age} NOT IN (?, ?, ?)');
    }

    #[Test]
    public function testInOperatorWithBadArrayParameter(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('status', 'IN', [['foo'], ['active', 'blocked']]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {status} IN (?, ?)');

        // Database doesn't validate parameter values in the array
        SqlAssert::sameParameters($select, [
            ['foo'],
            ['active', 'blocked'],
        ]);
    }

    #[Test]
    #[ExpectException(UnexpectedOperatorException::class)]
    public function testBadArrayParameterInShortWhere(): void
    {
        $this->database
            ->select()
            ->from(['users'])
            ->where(
                [
                    'status' => ['LIKE' => ['active', 'blocked']],
                ],
            )->sqlStatement();
    }

    #[Test]
    public function testGoodArrayParameter(): void
    {
        $p = new Parameter(['active', 'blocked']);

        $select = $this->database->select()
            ->from(['users'])
            ->where('status', 'IN', $p);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {status} IN (?, ?)');

        $p->setValue(['active']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {status} IN (?)');
    }

    #[Test]
    public function testGoodArrayParameterInShortWhere(): void
    {
        $p = new Parameter(['active', 'blocked']);

        $select = $this->database->select()
            ->from(['users'])
            ->where(
                [
                    'status' => ['IN' => $p],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {status} IN (?, ?)');

        $p->setValue(['active']);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {status} IN (?)');
    }

    #[Test]
    public function testInAndNotInWithArrayInShortWhere(): void
    {
        $status = ['active', 'blocked'];
        $name = ['bar', 'foo', 'baz'];

        $select = $this->database->select()
            ->from(['users'])
            ->where(
                [
                    'status' => ['IN' => $status],
                    'name' => ['NOT IN' => $name],
                ],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({status} IN (?, ?) AND {name} NOT IN (?, ?, ?))');
    }

    #[Test]
    public function testDirectIsNull(): void
    {
        $select = $this->database->select()->from(['users'])
            ->where('name', 'is', null);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} IS NULL');
    }

    #[Test]
    public function testDirectIsNot(): void
    {
        $select = $this->database->select()->from(['users'])
            ->where('name', 'is not', null);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} IS NOT NULL');
    }

    #[Test]
    public function testSelectForUpdate(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Antony')
            ->forUpdate();

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? FOR UPDATE');
    }

    #[Test]
    public function testSelectWithParametricExpression(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Antony')
            ->orWhere(new Expression('RANGE(balance, price) = ?', 10));

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR RANGE({balance}, {price}) = ?');

        SqlAssert::sameParameters($select, [
            'Antony',
            10,
        ]);
    }

    #[Test]
    public function testSelectWithParametricExpression2(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(
                new Expression('RANGE(?, ?)', 101, 102),
                '&&',
                new Expression('RANGE(?, ?)', 103, 104),
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE RANGE(?, ?) && RANGE(?, ?)');

        SqlAssert::sameParameters($select, [
            101,
            102,
            103,
            104,
        ]);
    }

    #[Test]
    public function testSelectWithParametricExpression3(): void
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(
                new Expression('RANGE(?, ?)', 101, 102),
                new Expression('RANGE(name, ?)', 600),
                new Expression('RANGE(?, ?)', 103, 104),
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE RANGE(?, ?) RANGE({name}, ?) RANGE(?, ?)');

        SqlAssert::sameParameters($select, [
            101,
            102,
            600,
            103,
            104,
        ]);
    }

    #[Test]
    public function testSelectWithFragmentedColumns(): void
    {
        $rankQ = \sprintf(
            'CASE WHEN %s ILIKE ? THEN 1 WHEN %s ILIKE ? THEN 2 WHEN %s ILIKE ? THEN 3 END AS rank',
            'field1', // name
            'field2', // name
            'field3', // name
        );

        $select = $this->database
            ->select()
            ->from(['users'])
            ->columns([new Fragment($rankQ, 1, 2, 3)])
            ->where('id', '>', 4);

        SqlAssert::same($select, 'SELECT CASE WHEN field1 ILIKE ? THEN 1 WHEN field2 ILIKE ? THEN2 '
        . 'WHEN field3 ILIKE ? THEN 3 END AS rank FROM {users} WHERE {id} > ?');

        SqlAssert::sameParameters($select, [
            1,
            2,
            3,
            4,
        ]);

        // check cached
        $rankQ = \sprintf(
            'CASE WHEN %s ILIKE ? THEN 1 WHEN %s ILIKE ? THEN 2 WHEN %s ILIKE ? THEN 3 END AS rank',
            'field1', // name
            'field2', // name
            'field3', // name
        );

        $select = $this->database
            ->select()
            ->from(['users'])
            ->columns([new Fragment($rankQ, 1, 2, 3)])
            ->where('id', '>', 4);

        SqlAssert::same($select, 'SELECT CASE WHEN field1 ILIKE ? THEN 1 WHEN field2 ILIKE ? '
        . 'THEN2 WHEN field3 ILIKE ? THEN 3 END AS rank FROM {users} WHERE {id} > ?');

        SqlAssert::sameParameters($select, [
            1,
            2,
            3,
            4,
        ]);
    }

    #[Test]
    public function testWhereInWithoutSpecifiedOperator(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                'uuid',
                new Parameter(['12345678-1234-1234-1234-123456789012', '12345678-1234-1234-1234-123456789013']),
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {uuid} IN (?, ?)');

        SqlAssert::sameParameters($select, ['12345678-1234-1234-1234-123456789012', '12345678-1234-1234-1234-123456789013']);
    }

    #[Test]
    public function testWhereInWithEqualSpecifiedOperator(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                'uuid',
                '=',
                new Parameter(['12345678-1234-1234-1234-123456789012', '12345678-1234-1234-1234-123456789013']),
            )->orWhere(
                'uuid',
                '=',
                ['23456789-1234-1234-1234-123456789012', '23456789-1234-1234-1234-123456789013'],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {uuid} IN (?, ?) OR {uuid} IN (?, ?)');

        SqlAssert::sameParameters($select, [
            '12345678-1234-1234-1234-123456789012',
            '12345678-1234-1234-1234-123456789013',
            '23456789-1234-1234-1234-123456789012',
            '23456789-1234-1234-1234-123456789013',
        ]);
    }

    #[Test]
    public function testWhereInWithNotEqualSpecifiedOperator(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(
                'uuid',
                '!=',
                new Parameter(['12345678-1234-1234-1234-123456789012', '12345678-1234-1234-1234-123456789013']),
            )->orWhere(
                'uuid',
                '!=',
                ['23456789-1234-1234-1234-123456789012', '23456789-1234-1234-1234-123456789013'],
            );

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {uuid} NOT IN (?, ?) OR {uuid} NOT IN (?, ?)');

        SqlAssert::sameParameters($select, [
            '12345678-1234-1234-1234-123456789012',
            '12345678-1234-1234-1234-123456789013',
            '23456789-1234-1234-1234-123456789012',
            '23456789-1234-1234-1234-123456789013',
        ]);
    }

    #[Test]
    public function testFragmentInWhereInClause(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('uuid', new Fragment('UUID_TO_BIN(?)', '12345678-1234-1234-1234-123456789012'))
            ->andWhere('uuid', 'IN', [
                new Fragment('UUID_TO_BIN(?)', '12345678-1234-1234-1234-123456789013'),
                new Fragment('UUID_TO_BIN(?)', '12345678-1234-1234-1234-123456789014'),
            ]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {uuid} = UUID_TO_BIN (?) AND {uuid} IN (UUID_TO_BIN (?), UUID_TO_BIN (?))');

        SqlAssert::sameParameters($select, [
            '12345678-1234-1234-1234-123456789012',
            '12345678-1234-1234-1234-123456789013',
            '12345678-1234-1234-1234-123456789014',
        ]);
    }

    /**
     * Issue https://github.com/cycle/database/issues/49
     */
    #[Test]
    public function testLeftJoinQuoting(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->leftJoin('_1SCONST _1SCONST2(NOLOCK)')
            ->on('SC3271.ID', '=', '_1SCONST2.OBJID');

        SqlAssert::same($select, 'SELECT * FROM {users} LEFT JOIN {_1SCONST} _1SCONST2({NOLOCK}) ON {SC3271}.{ID} = {_1SCONST2}.{OBJID}');
    }

    // WHERE NOT
    #[Test]
    public function testSimpleWhereNot(): void
    {
        $select = $this->db()
            ->select('*')
            ->from('table')
            ->whereNot('name', 'John Doe');

        SqlAssert::same((string) $select, 'SELECT * FROM {table} WHERE NOT {name} = \'John Doe\'');
    }

    #[Test]
    public function testArrayWhereNot(): void
    {
        $select = $this->database->select()
            ->from('table')
            ->whereNot('id', 'IN', [1, 2, 3, 4]);

        SqlAssert::same($select, 'SELECT * FROM {table} WHERE NOT {id} IN (?, ?, ?, ?)');
        SqlAssert::sameParameters($select, [1, 2, 3, 4]);
    }

    #[Test]
    public function testSelectWithWhereNotWithOperator(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])->whereNot('name', 'LIKE', 'Anton%');

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE NOT {name} LIKE ?');
    }

    #[Test]
    public function testSelectWithWhereNotWithBetween(): void
    {
        $select = $this->database->select()->distinct()->from(['users'])->whereNot('balance', 'BETWEEN', 0, 1000);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {users} WHERE NOT {balance} BETWEEN ? AND ?');
    }

    #[Test]
    public function testWhereWithOrWhereNot(): void
    {
        $select = $this->db()
            ->select('*')
            ->from('table')
            ->where('status', 'active')
            ->orWhereNot('name', 'John Doe');

        SqlAssert::same((string) $select, 'SELECT * FROM {table} WHERE {status} = \'active\' OR NOT {name} = \'John Doe\'');
    }

    #[Test]
    public function testWhereWithAndWhereNot(): void
    {
        $select = $this->db()
            ->select('*')
            ->from('table')
            ->where('status', 'active')
            ->andWhereNot('name', 'John Doe');

        SqlAssert::same((string) $select, 'SELECT * FROM {table} WHERE {status} = \'active\' AND NOT {name} = \'John Doe\'');
    }

    #[Test]
    public function testWhereNotAndOrWhere(): void
    {
        $select = $this->db()
            ->select('*')
            ->from('table')
            ->whereNot('status', 'blocked')
            ->orWhere('id', 1);

        SqlAssert::same((string) $select, 'SELECT * FROM {table} WHERE NOT {status} = \'blocked\' OR {id} = 1');
    }

    #[Test]
    public function testCompileNestedQueryWithWhereNot(): void
    {
        $select = $this->db()
            ->select('*')
            ->from('table', 'table2')
            ->where(['name' => 'Antony'])
            ->whereNot('id', 'in', (new SelectQuery())->from('other')->columns('id')->where('x', 123));

        SqlAssert::same((string) $select, 'SELECT * FROM {table}, {table2} WHERE {name} = \'Antony\' AND NOT {id} IN (
            SELECT {id} FROM {other} WHERE {x} = 123)');

        SqlAssert::sameParameters($select, ['Antony', 123]);
    }

    #[Test]
    public function testPrefixedSelectWithFullySpecificColumnNameInWhereNot(): void
    {
        $select = $this->db('prefixed', 'prefix_')
            ->select()
            ->distinct()
            ->from(['users'])
            ->whereNot('users.balance', 0);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {prefix_users} WHERE NOT {prefix_users}.{balance} = ?');
    }

    #[Test]
    public function testPrefixedSelectWithFullySpecificColumnNameInWhereNotButAliased(): void
    {
        $select = $this->db('prefixed', 'prefix_')->select()->distinct()->from(['users as u'])
            ->whereNot('u.balance', 0);

        SqlAssert::same($select, 'SELECT DISTINCT * FROM {prefix_users} AS {u} WHERE NOT {u}.{balance} = ?');
    }

    #[Test]
    public function testShortWhereNotMultiple(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->whereNot([
                'name' => 'John Doe',
                'value' => 1,
            ]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE NOT ({name} = ? AND {value} = ?)');
    }

    #[Test]
    public function testWhereNotWithSequenceCalls(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->whereNot('status', 'blocked')
            ->where('email_confirmed', true)
            ->whereNot('name', 'John Doe')
            ->orWhere('id', 1);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE NOT {status} = ? AND {email_confirmed} = ? AND NOT {name} = ? OR {id}=?');
    }

    #[Test]
    public function testWrapWhereGroupsExistingConditions(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere('name', 'John')
            ->wrapWhere()
            ->where('active', true);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({name} = ? OR {name} = ?) AND {active} = ?');
    }

    #[Test]
    public function testWrapWhereProtectsScopeFromLaterOrWhere(): void
    {
        // Models a soft-delete-style scope: condition added first, then wrapped,
        // then a user-supplied orWhere — without wrapWhere the scope would be lost.
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('deleted_at', null)
            ->wrapWhere()
            ->orWhere('id', 5);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({deleted_at} IS NULL) OR {id} = ?');
    }

    #[Test]
    public function testWrapWhereOnEmptyWhereIsNoop(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->wrapWhere()
            ->where('active', true);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {active} = ?');
    }

    #[Test]
    public function testWrapWhereWithMultipleAndConditions(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('a', 1)
            ->where('b', 2)
            ->where('c', 3)
            ->wrapWhere()
            ->where('d', 4);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({a} = ? AND {b} = ? AND {c} = ?) AND {d} = ?');
    }

    #[Test]
    public function testWrapWhereWithMixedAndOrChain(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('a', 1)
            ->andWhere('b', 2)
            ->orWhere('c', 3)
            ->wrapWhere()
            ->where('d', 4);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({a} = ? AND {b} = ? OR {c} = ?) AND {d} = ?');
    }

    #[Test]
    public function testWrapWhereWithWhereNot(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->whereNot('status', 'blocked')
            ->orWhere('role', 'admin')
            ->wrapWhere()
            ->where('active', true);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE (NOT {status} = ? OR {role} = ?) AND {active} = ?');
    }

    #[Test]
    public function testWrapWhereAroundClosureGroup(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(static function (SelectQuery $q): void {
                $q->where('a', 1)->orWhere('b', 2);
            })
            ->wrapWhere()
            ->where('c', 3);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE (({a} = ? OR {b} = ?)) AND {c} = ?');
    }

    #[Test]
    public function testClosureGroupAddedAfterWrapWhere(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('a', 1)
            ->orWhere('b', 2)
            ->wrapWhere()
            ->andWhere(static function (SelectQuery $q): void {
                $q->where('c', 3)->orWhere('d', 4);
            });

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({a} = ? OR {b} = ?) AND ({c} = ? OR {d} = ?)');
    }

    #[Test]
    public function testWrapWhereCalledTwiceInARowIsIdempotentForSql(): void
    {
        // Double-wrap adds a redundant pair of parens but is semantically harmless.
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('a', 1)
            ->orWhere('b', 2)
            ->wrapWhere()
            ->wrapWhere()
            ->where('c', 3);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE (({a} = ? OR {b} = ?)) AND {c} = ?');
    }

    #[Test]
    public function testWrapWhereStackedScopes(): void
    {
        // Models multiple stacked ORM scopes: each scope wraps the current state and
        // appends its own AND-condition. Final SQL should always be logically equivalent
        // to `user AND s1 AND s2`, with each layer isolated by parentheses.
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('a', 1)
            ->orWhere('a', 2)
            // first scope
            ->wrapWhere()
            ->where('s1', 'x')
            // second scope
            ->wrapWhere()
            ->where('s2', 'y');

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE (({a} = ? OR {a} = ?) AND {s1} = ?) AND {s2} = ?');
    }

    #[Test]
    public function testWrapWhereThenOrWhereNot(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('a', 1)
            ->andWhere('b', 2)
            ->wrapWhere()
            ->orWhereNot('c', 3);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({a} = ? AND {b} = ?) OR NOT {c} = ?');
    }

    #[Test]
    public function testWrapWhereWithArraySyntaxAndOrToken(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where([
                'name' => 'John',
                '@or' => [
                    ['status' => 'active'],
                    ['role' => 'admin'],
                ],
            ])
            ->wrapWhere()
            ->where('deleted_at', null);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE (({name} = ? AND ({status} = ? OR {role} = ?))) AND {deleted_at} IS NULL');
    }

    #[Test]
    public function testWrapWhereWithBetweenAndInOperators(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where('age', 'between', 18, 65)
            ->orWhere('role', 'IN', new Parameter(['admin', 'editor']))
            ->wrapWhere()
            ->where('active', true);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({age} BETWEEN ? AND ? OR {role} IN (?, ?)) AND {active} = ?');
    }

    #[Test]
    public function testWrapWhereWithLeadingOrWhere(): void
    {
        // Edge case: chain starts with orWhere — the leading boolean is suppressed by
        // the compiler when it's the first token, so behavior should be identical to
        // starting with where().
        $select = $this->database
            ->select()
            ->from(['users'])
            ->orWhere('a', 1)
            ->orWhere('b', 2)
            ->wrapWhere()
            ->where('c', 3);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE ({a} = ? OR {b} = ?) AND {c} = ?');
    }

    #[Test]
    public function testAndWhereNotWithArrayOr(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'John Doe'])
            ->andWhereNot([
                '@or' => [
                    ['id' => ['between' => [10, 100]], 'name' => 'John Doe'],
                    ['status' => 'disabled'],
                ],
            ]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND NOT (({id} BETWEEN ? AND ? AND {name} = ?) OR {status} = ?)');
    }

    #[Test]
    public function testAndWhereNotWithArrayAnd(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'John Doe'])
            ->andWhereNot([
                '@and' => [
                    ['id' => ['between' => [10, 100]], 'name' => 'John Doe'],
                    ['status' => 'disabled'],
                ],
            ]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? AND NOT (({id} BETWEEN ? AND ? AND {name} = ?) AND {status} = ?)');
    }

    #[Test]
    public function testOrWhereNotWithArrayOr(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'John Doe'])
            ->orWhereNot([
                '@or' => [
                    ['id' => ['between' => [10, 100]], 'name' => 'John Doe'],
                    ['status' => 'disabled'],
                ],
            ]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR NOT (({id} BETWEEN ? AND ? AND {name} = ?) OR {status} = ?)');
    }

    #[Test]
    public function testOrWhereNotWithArrayAnd(): void
    {
        $select = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'John Doe'])
            ->orWhereNot([
                '@and' => [
                    ['id' => ['between' => [10, 100]], 'name' => 'John Doe'],
                    ['status' => 'disabled'],
                ],
            ]);

        SqlAssert::same($select, 'SELECT * FROM {users} WHERE {name} = ? OR NOT (({id} BETWEEN ? AND ? AND {name} = ?) AND {status} = ?)');
    }

    #[Test]
    public function testSelectFromSubQuery(): void
    {
        $innerSelect = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'John Doe']);
        $injection = new SubQuery($innerSelect, 'u');

        $outerSelect = $this->database
            ->select()
            ->from($injection)
            ->where('u.id', '>', 10);

        SqlAssert::same($outerSelect, <<<SQL
                SELECT *
                FROM (SELECT * FROM {users} WHERE {name} = ?) AS {u}
                WHERE {u}.{id} > ?
                SQL);

        SqlAssert::sameParameters($outerSelect, [
            'John Doe',
            10,
        ]);
    }

    #[Test]
    public function testSelectFromTwoSubQuery(): void
    {
        $innerSelect1 = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'John Doe']);
        $injection1 = new SubQuery($innerSelect1, 'u');

        $innerSelect2 = $this->database
            ->select()
            ->from(['apartments'])
            ->where(['dom' => 12]);
        $injection2 = new SubQuery($innerSelect2, 'a');


        $outerSelect = $this->database
            ->select()
            ->from($injection1, $injection2);

        SqlAssert::same($outerSelect, <<<SQL
                SELECT *
                FROM
                    (SELECT * FROM {users} WHERE {name} = ?) AS {u},
                    (SELECT * FROM {apartments} WHERE {dom} = ?) AS {a}
                SQL);

        SqlAssert::sameParameters($outerSelect, [
            'John Doe',
            12,
        ]);
    }

    #[Test]
    public function testSelectSelectSubQuery(): void
    {
        $innerSelect = $this->database
            ->select()
            ->from(['users'])
            ->where(['name' => 'John Doe']);
        $injection = new SubQuery($innerSelect, 'u');

        $outerSelect = $this->database
            ->select(['*', $injection])
            ->from(['apartments']);

        SqlAssert::same($outerSelect, 'SELECT *, (SELECT * FROM {users} WHERE {name} = ?) AS {u} FROM {apartments}');

        SqlAssert::sameParameters($outerSelect, [
            'John Doe',
        ]);
    }
}
