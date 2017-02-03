<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Tests\Database;

use Mockery as m;
use Spiral\Database\Drivers\MySQL\MySQLDriver;
use Spiral\Database\Drivers\Postgres\PostgresDriver;
use Spiral\Database\Drivers\SQLite\SQLiteDriver;
use Spiral\Database\Drivers\SQLServer\SQLServerDriver;
use Spiral\Database\Entities\PDODriver;
use Spiral\Database\Entities\Quoter;

class QuoterTest extends \PHPUnit_Framework_TestCase
{
    public function testPrefixless()
    {
        $quoter = $this->makeQuoter();

        $this->assertEquals('*', $quoter->quote('*'));

        $quoter->reset();
        $this->assertEquals('"column"', $quoter->quote('column'));

        $quoter->reset();
        $this->assertEquals('"table"."column"', $quoter->quote('table.column'));

        $quoter->reset();
        $this->assertEquals('"table".*', $quoter->quote('table.*'));

        $quoter->reset();
        $this->assertEquals('"table_name"', $quoter->quote('table_name', true));

        $quoter->reset();
        $this->assertEquals(
            '"table"."column" AS "column_alias"',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter->reset();
        $this->assertEquals(
            '"table_name" AS "table_name"',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    public function testPrefixlessAggregations()
    {
        $quoter = $this->makeQuoter();

        $this->assertEquals('COUNT(*)', $quoter->quote('COUNT(*)'));

        $quoter->reset();
        $this->assertEquals('SUM("column")', $quoter->quote('SUM(column)'));

        $quoter->reset();
        $this->assertEquals('MIN("table"."column")', $quoter->quote('MIN(table.column)'));

        $quoter->reset();
        $this->assertEquals(
            'AVG("table"."column") AS "column_alias"',
            $quoter->quote('AVG(table.column) AS column_alias')
        );
    }

    public function testPrefixlessOperations()
    {
        $quoter = $this->makeQuoter();

        $this->assertEquals('"column_a" + "column_b"', $quoter->quote('column_a + column_b'));

        $quoter->reset();
        $this->assertEquals('"table"."column" * 10', $quoter->quote('table.column * 10'));

        $quoter->reset();
        $this->assertEquals(
            '("table"."column" + "some_column") / "other_table"."column_b"',
            $quoter->quote('(table.column + some_column) / other_table.column_b')
        );
    }

    public function testPrefixes()
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals('*', $quoter->quote('*'));

        $quoter->reset();
        $this->assertEquals('"column"', $quoter->quote('column'));

        $quoter->reset();
        $this->assertEquals('"p_table"."column"', $quoter->quote('table.column'));

        $quoter->reset();
        $this->assertEquals('"p_table".*', $quoter->quote('table.*'));

        $quoter->reset();
        $this->assertEquals('"p_table_name"', $quoter->quote('table_name', true));

        $quoter->reset();
        $this->assertEquals(
            '"p_table"."column" AS "column_alias"',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table_name" AS "table_name"',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    public function testPrefixesAggregations()
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals('COUNT(*)', $quoter->quote('COUNT(*)'));

        $quoter->reset();
        $this->assertEquals('SUM("column")', $quoter->quote('SUM(column)'));

        $quoter->reset();
        $this->assertEquals('MIN("p_table"."column")', $quoter->quote('MIN(table.column)'));

        $quoter->reset();
        $this->assertEquals(
            'AVG("p_table"."column") AS "column_alias"',
            $quoter->quote('AVG(table.column) AS column_alias')
        );
    }

    public function testPrefixesOperations()
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals('"column_a" + "column_b"', $quoter->quote('column_a + column_b'));

        $quoter->reset();
        $this->assertEquals('"p_table"."column" * 10', $quoter->quote('table.column * 10'));

        $quoter->reset();
        $this->assertEquals(
            '("p_table"."column" + "some_column") / "p_other_table"."column_b" AS "xxx"',
            $quoter->quote('(table.column + some_column) / other_table.column_b AS xxx')
        );
    }

    public function testAliases()
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals('"p_table"."column"', $quoter->quote('table.column'));
        $this->assertEquals('"p_table_name"', $quoter->quote('table_name', true));

        $this->assertEquals(
            '"p_table_name" AS "bubble"',
            $quoter->quote('table_name AS bubble', true));

        $this->assertEquals(
            '"bubble"."column" AS "column_alias"',
            $quoter->quote('bubble.column AS column_alias')
        );

        $this->assertEquals(
            '"p_table_name" AS "table_name"',
            $quoter->quote('table_name AS table_name', true)
        );

        $this->assertEquals('"table_name"."column"', $quoter->quote('table_name.column'));

        $quoter->reset();
        $this->assertEquals('"p_table_name"."column"', $quoter->quote('table_name.column'));

        $this->assertEquals(
            '"p_bubble"."column" AS "column_alias"',
            $quoter->quote('bubble.column AS column_alias')
        );
    }

    public function testAliasesAggregations()
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals(
            '"p_table_name" AS "bubble"',
            $quoter->quote('table_name AS bubble', true)
        );

        $this->assertEquals(
            'MIN("bubble"."column")',
            $quoter->quote('MIN(bubble.column)')
        );

        $this->assertEquals(
            'AVG("bubble"."column") AS "column_alias"',
            $quoter->quote('AVG(bubble.column) AS column_alias')
        );
    }

    public function testAliasesOperations()
    {
        $quoter = $this->makeQuoter('p_');
        $this->assertEquals(
            '"p_table_name" AS "bubble"',
            $quoter->quote('table_name AS bubble', true)
        );

        $this->assertEquals(
            '"bubble"."column" * 10 + "p_other_table"."column_x"',
            $quoter->quote('bubble.column * 10 + other_table.column_x')
        );

        $this->assertEquals(
            '("p_table"."column" + "some_column") / "p_yolo"."column_b"',
            $quoter->quote('(table.column + some_column) / yolo.column_b')
        );

        $this->assertEquals(
            '("p_table"."column" + "some_column") / "bubble"."column_b" AS "xxx"',
            $quoter->quote('(table.column + some_column) / bubble.column_b AS xxx')
        );
    }

    public function testCollisions()
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals(
            '"p_table"."column" AS "bubble"',
            $quoter->quote('table.column AS bubble')
        );

        $this->assertEquals('"bubble"', $quoter->quote('bubble', false));
        $this->assertEquals('"p_bubble"', $quoter->quote('bubble', true));

        $this->assertEquals(
            '"p_bubble"."column" AS "new_bubble"',
            $quoter->quote('bubble.column AS new_bubble')
        );

        $this->assertEquals(
            '"p_new_bubble" AS "x_bubble"',
            $quoter->quote('new_bubble AS x_bubble', true)
        );

        $this->assertEquals('"p_new_bubble"', $quoter->quote('new_bubble', true));
        $this->assertEquals('"x_bubble"', $quoter->quote('x_bubble', true));
    }

    public function testMySQLlPrefixes()
    {
        $quoter = $this->makeQuoter('p_', MySQLDriver::class);

        $this->assertEquals(
            '*',
            $quoter->quote('*')
        );

        $quoter->reset();
        $this->assertEquals(
            '`column`',
            $quoter->quote('column')
        );

        $quoter->reset();
        $this->assertEquals(
            '`p_table`.`column`',
            $quoter->quote('table.column')
        );

        $quoter->reset();
        $this->assertEquals(
            '`p_table`.*',
            $quoter->quote('table.*')
        );

        $quoter->reset();
        $this->assertEquals(
            '`p_table_name`',
            $quoter->quote('table_name', true)
        );

        $quoter->reset();
        $this->assertEquals(
            '`p_table`.`column` AS `column_alias`',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter->reset();
        $this->assertEquals(
            '`p_table_name` AS `table_name`',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    public function testPostgresPrefixes()
    {
        $quoter = $this->makeQuoter('p_', PostgresDriver::class);

        $this->assertEquals(
            '*',
            $quoter->quote('*')
        );

        $quoter->reset();
        $this->assertEquals(
            '"column"',
            $quoter->quote('column')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table"."column"',
            $quoter->quote('table.column')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table".*',
            $quoter->quote('table.*')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table_name"',
            $quoter->quote('table_name', true)
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table"."column" AS "column_alias"',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table_name" AS "table_name"',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    public function testSQLitePrefixes()
    {
        $quoter = $this->makeQuoter("p_", SQLiteDriver::class);

        $this->assertEquals(
            '*',
            $quoter->quote('*')
        );

        $quoter->reset();
        $this->assertEquals(
            '"column"',
            $quoter->quote('column')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table"."column"',
            $quoter->quote('table.column')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table".*',
            $quoter->quote('table.*')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table_name"',
            $quoter->quote('table_name', true)
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table"."column" AS "column_alias"',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter->reset();
        $this->assertEquals(
            '"p_table_name" AS "table_name"',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    public function testSQLServerPrefixes()
    {
        $quoter = $this->makeQuoter('p_', SQLServerDriver::class);

        $this->assertEquals(
            '*',
            $quoter->quote('*')
        );

        $quoter->reset();
        $this->assertEquals(
            '[column]',
            $quoter->quote('column')
        );

        $quoter->reset();
        $this->assertEquals(
            '[p_table].[column]',
            $quoter->quote('table.column')
        );

        $quoter->reset();
        $this->assertEquals(
            '[p_table].*',
            $quoter->quote('table.*')
        );

        $quoter->reset();
        $this->assertEquals(
            '[p_table_name]',
            $quoter->quote('table_name', true)
        );

        $quoter->reset();
        $this->assertEquals(
            '[p_table].[column] AS [column_alias]',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter->reset();
        $this->assertEquals(
            '[p_table_name] AS [table_name]',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    /**
     * Get instance of quoter.
     *
     * @param string $prefix
     * @param string $driver Driver class.
     *
     * @return Quoter
     */
    protected function makeQuoter($prefix = '', $driver = PDODriver::class)
    {
        /**
         * @var PDODriver $driver
         */
        $driver = m::mock($driver)->makePartial();

        return new Quoter($driver, $prefix);
    }
}
