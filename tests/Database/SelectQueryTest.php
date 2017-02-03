<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Tests\Database;

use Spiral\Database\Builders\SelectQuery;
use Spiral\Database\Entities\Database;
use Spiral\Database\Injections\Expression;
use Spiral\Database\Injections\Fragment;
use Spiral\Database\Injections\Parameter;
use Spiral\Database\Schemas\Prototypes\AbstractTable;
use Spiral\Pagination\PaginatorAwareInterface;

abstract class SelectQueryTest extends BaseQueryTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->database();
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

    //Generic behaviours

    public function testSimpleSelection()
    {
        $select = $this->database->select()->from('table');
        $this->assertSame($this->database->getDriver(), $select->getDriver());

        $this->assertSame(['table'], $select->getTables());

        //Test __debugInfo
        $this->assertInternalType('array', $select->__debugInfo());

        $this->assertSameQuery("SELECT * FROM {table}", $select);
    }

    public function testMultipleTablesSelection()
    {
        $select = $this->database->select()->from(['tableA', 'tableB']);

        $this->assertSameQuery("SELECT * FROM {tableA}, {tableB}", $select);
    }

    public function testSelectDistinct()
    {
        $select = $this->database->select()->distinct()->from(['table']);

        $this->assertSameQuery("SELECT DISTINCT * FROM {table}", $select);
    }

    public function testSelectWithSimpleWhere()
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('name', 'Anton');

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testSelectWithSimpleWhereNull()
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('name', null);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testSelectWithSimpleWhereNotNull()
    {
        $select = $this->database->select()->distinct()->from(['users'])->where('name', '!=', null);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} != ?",
            $select
        );
    }

    public function testSelectWithWhereWithOperator()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->where('name', 'LIKE', 'Anton%');

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} LIKE ?",
            $select
        );
    }

    public function testSelectWithWhereWithBetween()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->where('balance', 'BETWEEN', 0, 1000);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {balance} BETWEEN ? AND ?",
            $select
        );
    }

    public function testSelectWithWhereWithNotBetween()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->where('balance', 'NOT BETWEEN', 0, 1000);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {balance} NOT BETWEEN ? AND ?",
            $select
        );
    }

    /**
     * @expectedException \Spiral\Database\Exceptions\BuilderException
     * @expectedExceptionMessage Between statements expects exactly 2 values
     */
    public function testSelectWithWhereBetweenBadValue()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->where('balance', 'BETWEEN', 0);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {balance} NOT BETWEEN ? AND ?",
            $select
        );
    }

    public function testSelectWithFullySpecificColumnNameInWhere()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->where('users.balance', 12);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {users}.{balance} = ?",
            $select
        );
    }

    public function testPrefixedSelectWithFullySpecificColumnNameInWhere()
    {
        $select = $this->database('prefixed', 'prefix_')->select()->distinct()->from(['users'])
            ->where('users.balance', 12);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {prefix_users} WHERE {prefix_users}.{balance} = ?",
            $select
        );
    }

    public function testPrefixedSelectWithFullySpecificColumnNameInWhereButAliased()
    {
        $select = $this->database('prefixed', 'prefix_')->select()->distinct()->from(['users as u'])
            ->where('u.balance', 12);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {prefix_users} AS {u} WHERE {u}.{balance} = ?",
            $select
        );
    }

    //Simple combinations testing

    public function testSelectWithWhereAndWhere()
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->andWhere('balance', '>', 1);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} = ? AND {balance} > ?",
            $select
        );
    }

    public function testSelectWithWhereAndFallbackWhere()
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->where('balance', '>', 1);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} = ? AND {balance} > ?",
            $select
        );
    }

    public function testSelectWithWhereOrWhere()
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere('balance', '>', 1);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} = ? OR {balance} > ?",
            $select
        );
    }

    public function testSelectWithWhereOrWhereAndWhere()
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere('balance', '>', 1)
            ->andWhere('value', 'IN', new Parameter([10, 12]));

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} WHERE {name} = ? OR {balance} > ? AND {value} IN (?, ?)",
            $select
        );
    }

    //Combinations thought closures

    public function testWhereOfOrWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->andWhere(function (SelectQuery $select) {
                $select->orWhere('value', '>', 10)->orWhere('value', '<', 1000);
            });

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? AND ({value} > ? OR {value} < ?)",
            $select
        );
    }

    public function testWhereOfAndWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->andWhere(function (SelectQuery $select) {
                $select->where('value', '>', 10)->andWhere('value', '<', 1000);
            });

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? AND ({value} > ? AND {value} < ?)",
            $select
        );
    }

    public function testOrWhereOfOrWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere(function (SelectQuery $select) {
                $select->orWhere('value', '>', 10)->orWhere('value', '<', 1000);
            });

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? OR ({value} > ? OR {value} < ?)",
            $select
        );
    }

    public function testOrWhereOfAndWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', 'Anton')
            ->orWhere(function (SelectQuery $select) {
                $select->where('value', '>', 10)->andWhere('value', '<', 1000);
            });

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? OR ({value} > ? AND {value} < ?)",
            $select
        );
    }

    //Short where form

    public function testShortWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testShortWhereWithCondition()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where([
                'name' => [
                    'like' => 'Anton',
                    '!='   => 'Antony'
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE ({name} LIKE ? AND {name} != ?)",
            $select
        );
    }

    public function testShortWhereWithBetweenCondition()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where([
                'value' => [
                    'between' => [1, 2]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {value} BETWEEN ? AND ?",
            $select
        );
    }

    public function testShortWhereWithNotBetweenCondition()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where([
                'value' => [
                    'not between' => [1, 2]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {value} NOT BETWEEN ? AND ?",
            $select
        );
    }

    /**
     * @expectedException \Spiral\Database\Exceptions\BuilderException
     * @expectedExceptionMessage Exactly 2 array values are required for between statement
     */
    public function testShortWhereWithBetweenConditionBadArguments()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where([
                'value' => [
                    'between' => [1]
                ]
            ]);

    }

    public function testShortWhereMultiple()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where([
                'name'  => 'Anton',
                'value' => 1
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE ({name} = ? AND {value} = ?)",
            $select
        );
    }

    public function testShortWhereMultipleButNotInAGroup()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->where(['value' => 1]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? AND {value} = ?",
            $select
        );
    }

    public function testShortWhereOrWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orWhere(['value' => 1]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? OR {value} = ?",
            $select
        );
    }

    public function testAndShortWhereOR()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->andWhere([
                '@or' => [
                    ['value' => 1],
                    ['value' => ['>' => 12]]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? AND ({value} = ? OR {value} > ?)",
            $select
        );
    }

    public function testOrShortWhereOR()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orWhere([
                '@or' => [
                    ['value' => 1],
                    ['value' => ['>' => 12]]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? OR ({value} = ? OR {value} > ?)",
            $select
        );
    }

    public function testAndShortWhereAND()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->andWhere([
                '@and' => [
                    ['value' => 1],
                    ['value' => ['>' => 12]]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? AND ({value} = ? AND {value} > ?)",
            $select
        );
    }

    public function testOrShortWhereAND()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orWhere([
                '@and' => [
                    ['value' => 1],
                    ['value' => ['>' => 12]]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? OR ({value} = ? AND {value} > ?)",
            $select
        );
    }

    /**
     * @expectedException \Spiral\Database\Exceptions\BuilderException
     * @expectedExceptionMessage Nested conditions should have defined operator
     */
    public function testBadShortExpression()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where([
                'status' => ['active', 'blocked']
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {balance} = ?",
            $select
        );
    }

    //Order By

    public function testOrderByAsc()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name');

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} ASC",
            $select
        );
    }

    public function testOrderByAsc2()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', SelectQuery::SORT_ASC);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} ASC",
            $select
        );
    }

    public function testOrderByAsc3()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', 'ASC');

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} ASC",
            $select
        );
    }

    public function testOrderByDesc()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', SelectQuery::SORT_DESC);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} DESC",
            $select
        );
    }

    public function testOrderByDesc3()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('name', 'DESC');

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? ORDER BY {name} DESC",
            $select
        );
    }

    public function testMultipleOrderBy()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('value', SelectQuery::SORT_ASC)
            ->orderBy('name', SelectQuery::SORT_DESC);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? ORDER BY {value} ASC, {name} DESC",
            $select
        );
    }

    public function testMultipleOrderByViaArray()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy([
                'value' => SelectQuery::SORT_ASC,
                'name'  => SelectQuery::SORT_DESC
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? ORDER BY {value} ASC, {name} DESC",
            $select
        );
    }

    public function testMultipleOrderByFullySpecified()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('users.value', SelectQuery::SORT_ASC);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? ORDER BY {users}.{value} ASC",
            $select
        );
    }

    public function testMultipleOrderByFullySpecifiedPrefixed()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->orderBy('users.value', SelectQuery::SORT_ASC);

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} WHERE {name} = ? ORDER BY {prefix_users}.{value} ASC",
            $select
        );
    }

    public function testMultipleOrderByFullySpecifiedAliasedAndPrefixed()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users as u'])
            ->where(['name' => 'Anton'])
            ->orderBy('u.value', SelectQuery::SORT_ASC);

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} AS {u} WHERE {name} = ? ORDER BY {u}.{value} ASC",
            $select
        );
    }

    //Group By

    public function testGroupBy()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->groupBy('name');

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? GROUP BY {name}",
            $select
        );
    }

    public function testMultipleGroupByFullySpecified()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->groupBy('users.value');

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ? GROUP BY {users}.{value}",
            $select
        );
    }

    public function testMultipleGroupByFullySpecifiedPrefixed()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->where(['name' => 'Anton'])
            ->groupBy('users.value');

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} WHERE {name} = ? GROUP BY {prefix_users}.{value}",
            $select
        );
    }

    public function testMultipleGroupByFullySpecifiedAliasedAndPrefixed()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users as u'])
            ->where(['name' => 'Anton'])
            ->groupBy('u.value');

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} AS {u} WHERE {name} = ? GROUP BY {u}.{value}",
            $select
        );
    }

    //Column Tweaking

    public function testAllColumns()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testAllColumns1()
    {
        $select = $this->database->select('*')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testAllColumns2()
    {
        $select = $this->database->select(['*'])
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testAllColumns3()
    {
        $select = $this->database->select([])
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testAllColumns4()
    {
        $select = $this->database->select()
            ->columns('*')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testAllColumns5()
    {
        $select = $this->database->select()
            ->columns('users.*')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {users}.* FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testAllColumnsWithPrefix()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->columns('users.*')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {prefix_users}.* FROM {prefix_users} WHERE {name} = ?",
            $select
        );
    }

    public function testAllColumnsWithPrefixAliased()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->columns('u.*')
            ->from(['users as u'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {u}.* FROM {prefix_users} AS {u} WHERE {name} = ?",
            $select
        );
    }

    public function testOneColumn()
    {
        $select = $this->database->select()
            ->columns('name')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {name} FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testOneFullySpecifiedColumn()
    {
        $select = $this->database->select()
            ->columns('users.name')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {users}.{name} FROM {users} WHERE {name} = ?",
            $select
        );
    }

    public function testOneFullySpecifiedColumnWithPrefix()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->columns('users.name')
            ->from(['users'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {prefix_users}.{name} FROM {prefix_users} WHERE {name} = ?",
            $select
        );
    }

    public function testOneFullySpecifiedColumnWithPrefixButAliased()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->columns('u.name')
            ->from(['users as u'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {u}.{name} FROM {prefix_users} AS {u} WHERE {name} = ?",
            $select
        );
    }

    public function testColumnWithAlias()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->columns('u.name as u_name')
            ->from(['users as u'])
            ->where(['u_name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {u}.{name} AS {u_name} FROM {prefix_users} AS {u} WHERE {u_name} = ?",
            $select
        );
    }

    public function testMultipleColumns()
    {
        $select = $this->database->select()
            ->columns(['name', 'value'])
            ->from(['users as u'])
            ->where(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT {name}, {value} FROM {users} AS {u} WHERE {name} = ?",
            $select
        );
    }

    public function testColumnsWithFunctions()
    {
        $select = $this->database->select()
            ->columns(['SUM(u.balance)', 'COUNT(*)'])
            ->from(['users as u'])
            ->where(['name' => 'Anton'])
            ->groupBy('balance');

        $this->assertSameQuery(
            "SELECT SUM({u}.{balance}), COUNT(*) FROM {users} AS {u} WHERE {name} = ? GROUP BY {balance}",
            $select
        );
    }

    //HAVING, Generic behaviours

    public function testHavingSelectWithSimpleHaving()
    {
        $select = $this->database->select()->distinct()->from(['users'])->having('name', 'Anton');

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {name} = ?",
            $select
        );
    }

    public function testHavingSelectWithHavingWithOperator()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('name', 'LIKE', 'Anton%');

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {name} LIKE ?",
            $select
        );
    }

    public function testHavingSelectWithHavingWithBetween()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('balance', 'BETWEEN', 0, 1000);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {balance} BETWEEN ? AND ?",
            $select
        );
    }

    public function testHavingSelectWithHavingWithNotBetween()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('balance', 'NOT BETWEEN', 0, 1000);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {balance} NOT BETWEEN ? AND ?",
            $select
        );
    }

    /**
     * @expectedException \Spiral\Database\Exceptions\BuilderException
     * @expectedExceptionMessage Between statements expects exactly 2 values
     */
    public function testHavingSelectWithHavingBetweenBadValue()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('balance', 'BETWEEN', 0);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {balance} NOT BETWEEN ? AND ?",
            $select
        );
    }

    public function testHavingSelectWithFullySpecificColumnNameInHaving()
    {
        $select = $this->database->select()->distinct()->from(['users'])
            ->having('users.balance', 12);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {users}.{balance} = ?",
            $select
        );
    }

    public function testHavingPrefixedSelectWithFullySpecificColumnNameInHaving()
    {
        $select = $this->database('prefixed', 'prefix_')->select()->distinct()->from(['users'])
            ->having('users.balance', 12);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {prefix_users} HAVING {prefix_users}.{balance} = ?",
            $select
        );
    }

    public function testHavingPrefixedSelectWithFullySpecificColumnNameInHavingButAliased()
    {
        $select = $this->database('prefixed', 'prefix_')->select()->distinct()->from(['users as u'])
            ->having('u.balance', 12);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {prefix_users} AS {u} HAVING {u}.{balance} = ?",
            $select
        );
    }

    //HAVING, Simple combinations testing

    public function testHavingSelectWithHavingAndHaving()
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->having('name', 'Anton')
            ->andHaving('balance', '>', 1);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {name} = ? AND {balance} > ?",
            $select
        );
    }

    public function testHavingSelectWithHavingAndFallbackHaving()
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->having('name', 'Anton')
            ->having('balance', '>', 1);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {name} = ? AND {balance} > ?",
            $select
        );
    }

    public function testHavingSelectWithHavingOrHaving()
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->having('name', 'Anton')
            ->orHaving('balance', '>', 1);

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {name} = ? OR {balance} > ?",
            $select
        );
    }

    public function testHavingSelectWithHavingOrHavingAndHaving()
    {
        $select = $this->database->select()->distinct()
            ->from(['users'])
            ->having('name', 'Anton')
            ->orHaving('balance', '>', 1)
            ->andHaving('value', 'IN', new Parameter([10, 12]));

        $this->assertSameQuery(
            "SELECT DISTINCT * FROM {users} HAVING {name} = ? OR {balance} > ? AND {value} IN (?, ?)",
            $select
        );
    }

    //HAVING, Combinations thought closures

    public function testHavingHavingOfOrHaving()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having('name', 'Anton')
            ->andHaving(function (SelectQuery $select) {
                $select->orHaving('value', '>', 10)->orHaving('value', '<', 1000);
            });

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? AND ({value} > ? OR {value} < ?)",
            $select
        );
    }

    public function testHavingHavingOfAndHaving()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having('name', 'Anton')
            ->andHaving(function (SelectQuery $select) {
                $select->having('value', '>', 10)->andHaving('value', '<', 1000);
            });

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? AND ({value} > ? AND {value} < ?)",
            $select
        );
    }

    public function testHavingOrHavingOfOrHaving()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having('name', 'Anton')
            ->orHaving(function (SelectQuery $select) {
                $select->orHaving('value', '>', 10)->orHaving('value', '<', 1000);
            });

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? OR ({value} > ? OR {value} < ?)",
            $select
        );
    }

    public function testHavingOrHavingOfAndHaving()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having('name', 'Anton')
            ->orHaving(function (SelectQuery $select) {
                $select->having('value', '>', 10)->andHaving('value', '<', 1000);
            });

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? OR ({value} > ? AND {value} < ?)",
            $select
        );
    }

    //HAVING, Short having form

    public function testHavingShortHaving()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton']);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ?",
            $select
        );
    }

    public function testHavingShortHavingWithCondition()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having([
                'name' => [
                    'like' => 'Anton',
                    '!='   => 'Antony'
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING ({name} LIKE ? AND {name} != ?)",
            $select
        );
    }

    public function testHavingShortHavingWithBetweenCondition()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having([
                'value' => [
                    'between' => [1, 2]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {value} BETWEEN ? AND ?",
            $select
        );
    }

    public function testHavingShortHavingWithNotBetweenCondition()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having([
                'value' => [
                    'not between' => [1, 2]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {value} NOT BETWEEN ? AND ?",
            $select
        );
    }

    /**
     * @expectedException \Spiral\Database\Exceptions\BuilderException
     * @expectedExceptionMessage Exactly 2 array values are required for between statement
     */
    public function testHavingShortHavingWithBetweenConditionBadArguments()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having([
                'value' => [
                    'between' => [1]
                ]
            ]);

    }

    public function testHavingShortHavingMultiple()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having([
                'name'  => 'Anton',
                'value' => 1
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING ({name} = ? AND {value} = ?)",
            $select
        );
    }

    public function testHavingShortHavingMultipleButNotInAGroup()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->having(['value' => 1]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? AND {value} = ?",
            $select
        );
    }

    public function testHavingShortHavingOrHaving()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->orHaving(['value' => 1]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? OR {value} = ?",
            $select
        );
    }

    public function testHavingAndShortHavingOR()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->andHaving([
                '@or' => [
                    ['value' => 1],
                    ['value' => ['>' => 12]]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? AND ({value} = ? OR {value} > ?)",
            $select
        );
    }

    public function testHavingOrShortHavingOR()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->orHaving([
                '@or' => [
                    ['value' => 1],
                    ['value' => ['>' => 12]]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? OR ({value} = ? OR {value} > ?)",
            $select
        );
    }

    public function testHavingAndShortHavingAND()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->andHaving([
                '@and' => [
                    ['value' => 1],
                    ['value' => ['>' => 12]]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? AND ({value} = ? AND {value} > ?)",
            $select
        );
    }

    public function testHavingOrShortHavingAND()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->having(['name' => 'Anton'])
            ->orHaving([
                '@and' => [
                    ['value' => 1],
                    ['value' => ['>' => 12]]
                ]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} HAVING {name} = ? OR ({value} = ? AND {value} > ?)",
            $select
        );
    }

    //Limit and offset, ATTENTION THIS SECTION IS DRIVER SPECIFIC!

    public function testLimitNoOffset()
    {
        $select = $this->database->select()->from(['users'])->limit(10);

        $this->assertSameQuery(
            "SELECT * FROM {users} LIMIT 10",
            $select
        );
    }

    public function testLimitAndOffset()
    {
        $select = $this->database->select()->from(['users'])->limit(10)->offset(20);

        $this->assertSame(10, $select->getLimit());
        $this->assertSame(20, $select->getOffset());

        $this->assertSameQuery(
            "SELECT * FROM {users} LIMIT 10 OFFSET 20",
            $select
        );
    }

    public function testOffsetNoLimit()
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            "SELECT * FROM {users} OFFSET 20",
            $select
        );
    }

    //Attention, this is proper way!
    public function testLimitAndOffsetAndOrderBy()
    {
        $select = $this->database->select()->from(['users'])->limit(10)->orderBy('name')->offset(20);

        $this->assertSame(10, $select->getLimit());
        $this->assertSame(20, $select->getOffset());

        $this->assertSameQuery(
            "SELECT * FROM {users} ORDER BY {name} ASC LIMIT 10 OFFSET 20",
            $select
        );
    }

    //Fragments

    public function testColumnNameAsFragment()
    {
        $select = $this->database->select(new Fragment('_ROW_ID_'))->from(['users']);

        $this->assertSameQuery(
            "SELECT _ROW_ID_ FROM {users}",
            $select
        );
    }

    public function testWhereValueAsFragment()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('balance', '=', new Fragment('(1 + 2) / 3'));

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {balance} = (1 + 2) / 3",
            $select
        );
    }

    public function testShortWhereValueAsFragment()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where(['balance' => new Fragment('(1 + 2) / 3')]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {balance} = (1 + 2) / 3",
            $select
        );
    }

    public function testWhereOperatorAsFragment()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->where('name', new Fragment('SUPERLIKE'), 'Anton');

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {name} SUPERLIKE ?",
            $select
        );
    }

    public function testOrderByFragment()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->orderBy(new Fragment('RAND()'));

        $this->assertSameQuery(
            "SELECT * FROM {users} ORDER BY RAND() ASC",
            $select
        );
    }

    //Please not this example
    public function testGroupByFragment()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->groupBy(new Fragment('RESOLVE_USER(users.id)'));

        $this->assertSameQuery(
            "SELECT * FROM {users} GROUP BY RESOLVE_USER(users.id)",
            $select
        );

        //Note: see Expressions
    }

    //Expressions

    public function testColumnNameAsExpression()
    {
        $select = $this->database->select(new Expression('name'))->from(['users']);

        $this->assertSameQuery(
            "SELECT {name} FROM {users}",
            $select
        );
    }

    public function testColumnNameAndTableAsExpression()
    {
        $select = $this->database->select(new Expression('users.name'))->from(['users']);

        $this->assertSameQuery(
            "SELECT {users}.{name} FROM {users}",
            $select
        );
    }

    public function testColumnNameAndTableAsExpressionPrefixed()
    {
        $select = $this->database('prefixed', 'prefix_')
            ->select(new Expression('users.name'))
            ->from(['users']);

        $this->assertSameQuery(
            "SELECT {prefix_users}.{name} FROM {prefix_users}",
            $select
        );
    }

    public function testColumnNameAndTableAsExpressionPrefixedAliased()
    {
        $select = $this->database('prefixed', 'prefix_')
            ->select(new Expression('u.name'))
            ->from(['users as u']);

        $this->assertSameQuery(
            "SELECT {u}.{name} FROM {prefix_users} AS {u}",
            $select
        );
    }

    public function testWhereValueAsExpression()
    {
        $select = $this->database->select()->from(['users'])
            ->where('balance', '>', new Expression('origin_balance'));

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {balance} > {origin_balance}",
            $select
        );
    }

    public function testWhereValueAndTableAsExpression()
    {
        $select = $this->database->select()->from(['users'])
            ->where('balance', '>', new Expression('users.origin_balance'));

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {balance} > {users}.{origin_balance}",
            $select
        );
    }

    public function testWhereValueAndTableAsExpressionPrefixed()
    {
        $select = $this->database('prefixed', 'prefix_')->select()->from(['users'])
            ->where('balance', '>', new Expression('users.origin_balance'));

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} WHERE {balance} > {prefix_users}.{origin_balance}",
            $select
        );
    }

    public function testWhereValueAndTableAsExpressionPrefixedAliased()
    {
        $select = $this->database('prefixed', 'prefix_')->select()->from(['users as u'])
            ->where('balance', '>', new Expression('u.origin_balance'));

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} AS {u} WHERE {balance} > {u}.{origin_balance}",
            $select
        );
    }

    public function testShortWhereValueAsExpressionPrefixed()
    {
        $select = $this->database('prefixed', 'prefix_')->select()->from(['users'])
            ->where([
                'balance' => ['>' => new Expression('users.origin_balance')]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} WHERE {balance} > {prefix_users}.{origin_balance}",
            $select
        );
    }

    public function testOrderByExpression()
    {
        $select = $this->database('prefixed', 'prefix_')->select()->from(['users'])
            ->orderBy(new Expression('users.balance'));

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} ORDER BY {prefix_users}.{balance} ASC",
            $select
        );
    }

    public function testGroupByExpression()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->groupBy(new Expression('RESOLVE_USER(users.id)'));

        $this->assertSameQuery(
            "SELECT * FROM {users} GROUP BY RESOLVE_USER({users}.{id})",
            $select
        );
    }

    public function testGroupByExpressionWithPrefix()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->groupBy(new Expression('RESOLVE_USER(users.id)'));

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} GROUP BY RESOLVE_USER({prefix_users}.{id})",
            $select
        );
    }

    //Parameters (writing only)

    public function testWhereValueAsParameter()
    {
        $p = new Parameter(12);

        $select = $this->database->select()
            ->from(['users'])
            ->where('balance', $p);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {balance} = ?",
            $select
        );
    }

    public function testShortWhereValueAsParameter()
    {
        $p = new Parameter(12);

        $select = $this->database->select()
            ->from(['users'])
            ->where(['balance' => $p]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {balance} = ?",
            $select
        );
    }

    /**
     * @expectedException \Spiral\Database\Exceptions\BuilderException
     * @expectedExceptionMessage Arrays must be wrapped with Parameter instance
     */
    public function testBadArrayParameter()
    {
        $this->database->select()
            ->from(['users'])
            ->where('status', 'IN', ['active', 'blocked']);
    }

    /**
     * @expectedException \Spiral\Database\Exceptions\BuilderException
     * @expectedExceptionMessage Arrays must be wrapped with Parameter instance
     */
    public function testBadArrayParameterInShortWhere()
    {
        $this->database->select()
            ->from(['users'])
            ->where([
                'status' => ['IN' => ['active', 'blocked']]
            ]);
    }

    public function testGoodArrayParameter()
    {
        $p = new Parameter(['active', 'blocked']);

        $select = $this->database->select()
            ->from(['users'])
            ->where('status', 'IN', $p);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {status} IN (?, ?)",
            $select
        );

        $p->setValue(['active']);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {status} IN (?)",
            $select
        );
    }

    public function testGoodArrayParameterInShortWhere()
    {
        $p = new Parameter(['active', 'blocked']);

        $select = $this->database->select()
            ->from(['users'])
            ->where([
                'status' => ['IN' => $p]
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {status} IN (?, ?)",
            $select
        );

        $p->setValue(['active']);

        $this->assertSameQuery(
            "SELECT * FROM {users} WHERE {status} IN (?)",
            $select
        );
    }

    //Joins

    public function testLeftJoin0()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('LEFT', 'photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testLeftJoin1()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('LEFT', 'photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testLeftJoin2()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testLeftJoin3()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testRightJoin0()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('RIGHT', 'photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            "SELECT * FROM {users} RIGHT JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testRightJoin1()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('RIGHT', 'photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            "SELECT * FROM {users} RIGHT JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testRightJoin2()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->rightJoin('photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            "SELECT * FROM {users} RIGHT JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testRightJoin3()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->rightJoin('photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            "SELECT * FROM {users} RIGHT JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testInnerJoin0()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('INNER', 'photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            "SELECT * FROM {users} INNER JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testInnerJoin1()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->join('INNER', 'photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            "SELECT * FROM {users} INNER JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testInnerJoin2()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->innerJoin('photos')->on('photos.user_id', 'users.id');

        $this->assertSameQuery(
            "SELECT * FROM {users} INNER JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    public function testInnerJoin3()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->innerJoin('photos')->on(['photos.user_id' => 'users.id']);

        $this->assertSameQuery(
            "SELECT * FROM {users} INNER JOIN {photos} ON {photos}.{user_id} = {users}.{id}",
            $select
        );
    }

    //Join with WHERE

    public function testJoinWithComplexWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')->on('photos.user_id', 'users.id')->onWhere('photos.public', true);

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id} AND {photos}.{public} = ?",
            $select
        );
    }

    public function testJoinWithComplexOrWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->orOn('photos.group_id', 'users.group_id');

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id} OR {photos}.{group_id} = {users}.{group_id}",
            $select
        );
    }

    public function testJoinWithComplexAndWhere()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->andOn('photos.group_id', 'users.group_id');

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id} AND {photos}.{group_id} = {users}.{group_id}",
            $select
        );
    }

    public function testJoinWithComplexAndWhereDefaults()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->on('photos.group_id', 'users.group_id');

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} ON {photos}.{user_id} = {users}.{id} AND {photos}.{group_id} = {users}.{group_id}",
            $select
        );
    }

    public function testJoinWithComplexWhereAndOR()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->onWhere('photos.public', true)
            ->orOnWhere('photos.magic', '>', 900);

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} "
            . "ON {photos}.{user_id} = {users}.{id} AND {photos}.{public} = ? OR {photos}.{magic} > ?",
            $select
        );
    }

    public function testJoinWithComplexWhereAnd()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->onWhere('photos.public', true)
            ->andOnWhere('photos.magic', '>', 900);

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} "
            . "ON {photos}.{user_id} = {users}.{id} AND {photos}.{public} = ? AND {photos}.{magic} > ?",
            $select
        );
    }

    public function testJoinWithComplexWhereAndDefaults()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos')
            ->on('photos.user_id', 'users.id')
            ->onWhere('photos.public', true)
            ->onWhere('photos.magic', '>', 900);

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} "
            . "ON {photos}.{user_id} = {users}.{id} AND {photos}.{public} = ? AND {photos}.{magic} > ?",
            $select
        );
    }

    //Join aliases

    public function testJoinAliases()
    {
        $select = $this->database->select()
            ->from(['users'])
            ->leftJoin('photos as p')
            ->on([
                'p.user_id' => 'users.id',
                'p.public'  => new Parameter(true)
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {users} LEFT JOIN {photos} AS {p} "
            . "ON ({p}.{user_id} = {users}.{id} AND {p}.{public} = ?)",
            $select
        );
    }

    public function testJoinAliasesWithPrefixes()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->leftJoin('photos as p')
            ->on([
                'p.user_id' => 'users.id',
                'p.public'  => new Parameter(true)
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} LEFT JOIN {prefix_photos} AS {p} "
            . "ON ({p}.{user_id} = {prefix_users}.{id} AND {p}.{public} = ?)",
            $select
        );
    }

    public function testJoinAliasesWithPrefixesAlternative()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users'])
            ->leftJoin('photos', 'p')
            ->on([
                'p.user_id' => 'users.id',
                'p.public'  => new Parameter(true)
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} LEFT JOIN {prefix_photos} AS {p} "
            . "ON ({p}.{user_id} = {prefix_users}.{id} AND {p}.{public} = ?)",
            $select
        );
    }

    public function testJoinAliasesWithPrefixesAndAliases()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users as u'])
            ->leftJoin('photos as p')
            ->on([
                'p.user_id' => 'u.id',
                'p.public'  => new Parameter(true)
            ]);

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} AS {u} LEFT JOIN {prefix_photos} AS {p} "
            . "ON ({p}.{user_id} = {u}.{id} AND {p}.{public} = ?)",
            $select
        );
    }

    //Complex verification example

    public function testComplexExample()
    {
        $statuses = new Parameter(['active', 'disabled']);

        $select = $this->database('prefixed', 'prefix_')
            ->select('COUNT(*)', 'groups.id', 'u.id', 'SUM(t.amount)')
            ->from(['users as u'])
            ->leftJoin('transactions as t')->on(['t.user_id' => 'u.id'])
            ->rightJoin('groups')->on(['groups.id' => 'u.group_id'])->onWhere('groups.public', true)
            ->where('u.status', 'IN', $statuses)
            ->orderBy('u.name', 'DESC')
            ->groupBy('u.id');

        $this->assertSameQuery(
            "SELECT COUNT(*), {prefix_groups}.{id}, {u}.{id}, SUM({t}.{amount}) "
            . "FROM {prefix_users} AS {u}"
            . "LEFT JOIN {prefix_transactions} AS {t} ON {t}.{user_id} = {u}.{id}"
            . "RIGHT JOIN {prefix_groups} ON {prefix_groups}.{id} = {u}.{group_id} AND {prefix_groups}.{public} = ?"
            . "WHERE {u}.{status} IN (?,?)"
            . "GROUP BY {u}.{id}"
            . "ORDER BY {u}.{name} DESC",
            $select
        );
    }

    public function testJoinQuery()
    {
        $select = $this->database('prefixed', 'prefix_')->select()
            ->from(['users as u'])
            ->leftJoin(
                $this->database('prefixed', 'prefix_')
                    ->select()->from('posts AS p')
                    ->where('p.user_id', new Expression('u.id')),
                'sub_posts'
            );

        $this->assertSameQuery(
            "SELECT * FROM {prefix_users} AS {u} LEFT JOIN (
                    SELECT * FROM {prefix_posts} AS {p}
                    WHERE {p}.{user_id} = {u}.{id}
                  ) AS {sub_posts} ",
            $select
        );
    }
}