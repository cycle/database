<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Driver\Quoter;

class QuoterTest extends TestCase
{
    public function testPrefixless(): void
    {
        $quoter = $this->makeQuoter();

        $this->assertEquals('*', $quoter->quote('*'));

        $quoter = clone $quoter;
        $this->assertEquals('"column"', $quoter->quote('column'));

        $quoter = clone $quoter;
        $this->assertEquals('"table"."column"', $quoter->quote('table.column'));

        $quoter = clone $quoter;
        $this->assertEquals('"table".*', $quoter->quote('table.*'));

        $quoter = clone $quoter;
        $this->assertEquals('"table_name"', $quoter->quote('table_name', true));

        $quoter = clone $quoter;
        $this->assertEquals(
            '"table"."column" AS "column_alias"',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '"table_name" AS "table_name"',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    public function testPrefixlessAggregations(): void
    {
        $quoter = $this->makeQuoter();

        $this->assertEquals('COUNT(*)', $quoter->quote('COUNT(*)'));

        $quoter = clone $quoter;
        $this->assertEquals('SUM("column")', $quoter->quote('SUM(column)'));

        $quoter = clone $quoter;
        $this->assertEquals('MIN("table"."column")', $quoter->quote('MIN(table.column)'));

        $quoter = clone $quoter;
        $this->assertEquals(
            'AVG("table"."column") AS "column_alias"',
            $quoter->quote('AVG(table.column) AS column_alias')
        );
    }

    public function testPrefixlessOperations(): void
    {
        $quoter = $this->makeQuoter();

        $this->assertEquals('"column_a" + "column_b"', $quoter->quote('column_a + column_b'));

        $quoter = clone $quoter;
        $this->assertEquals('"table"."column" * 10', $quoter->quote('table.column * 10'));

        $quoter = clone $quoter;
        $this->assertEquals(
            '("table"."column" + "some_column") / "other_table"."column_b"',
            $quoter->quote('(table.column + some_column) / other_table.column_b')
        );
    }

    public function testPrefixes(): void
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals('*', $quoter->quote('*'));

        $quoter = clone $quoter;
        $this->assertEquals('"column"', $quoter->quote('column'));

        $quoter = clone $quoter;
        $this->assertEquals('"p_table"."column"', $quoter->quote('table.column'));

        $quoter = clone $quoter;
        $this->assertEquals('"p_table".*', $quoter->quote('table.*'));

        $quoter = clone $quoter;
        $this->assertEquals('"p_table_name"', $quoter->quote('table_name', true));

        $quoter = clone $quoter;
        $this->assertEquals(
            '"p_table"."column" AS "column_alias"',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '"p_table_name" AS "table_name"',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    public function testPrefixesAggregations(): void
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals('COUNT(*)', $quoter->quote('COUNT(*)'));

        $quoter = clone $quoter;
        $this->assertEquals('SUM("column")', $quoter->quote('SUM(column)'));

        $quoter = clone $quoter;
        $this->assertEquals('MIN("p_table"."column")', $quoter->quote('MIN(table.column)'));

        $quoter = clone $quoter;
        $this->assertEquals(
            'AVG("p_table"."column") AS "column_alias"',
            $quoter->quote('AVG(table.column) AS column_alias')
        );
    }

    public function testPrefixesOperations(): void
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals('"column_a" + "column_b"', $quoter->quote('column_a + column_b'));

        $quoter = clone $quoter;
        $this->assertEquals('"p_table"."column" * 10', $quoter->quote('table.column * 10'));

        $quoter = clone $quoter;
        $this->assertEquals(
            '("p_table"."column" + "some_column") / "p_other_table"."column_b" AS "xxx"',
            $quoter->quote('(table.column + some_column) / other_table.column_b AS xxx')
        );
    }

    public function testAliases(): void
    {
        $quoter = $this->makeQuoter('p_');

        $this->assertEquals('"p_table"."column"', $quoter->quote('table.column'));
        $this->assertEquals('"p_table_name"', $quoter->quote('table_name', true));

        $this->assertEquals(
            '"p_table_name" AS "bubble"',
            $quoter->quote('table_name AS bubble', true)
        );

        $this->assertEquals(
            '"bubble"."column" AS "column_alias"',
            $quoter->quote('bubble.column AS column_alias')
        );

        $this->assertEquals(
            '"p_table_name" AS "table_name"',
            $quoter->quote('table_name AS table_name', true)
        );

        $this->assertEquals('"table_name"."column"', $quoter->quote('table_name.column'));

        $quoter = clone $quoter;
        $this->assertEquals('"p_table_name"."column"', $quoter->quote('table_name.column'));

        $this->assertEquals(
            '"p_bubble"."column" AS "column_alias"',
            $quoter->quote('bubble.column AS column_alias')
        );
    }

    public function testAliasesAggregations(): void
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

    public function testAliasesOperations(): void
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

    public function testCollisions(): void
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

    public function testMySQLlPrefixes(): void
    {
        $quoter = $this->makeQuoter('p_', '``');

        $this->assertEquals(
            '*',
            $quoter->quote('*')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '`column`',
            $quoter->quote('column')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '`p_table`.`column`',
            $quoter->quote('table.column')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '`p_table`.*',
            $quoter->quote('table.*')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '`p_table_name`',
            $quoter->quote('table_name', true)
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '`p_table`.`column` AS `column_alias`',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '`p_table_name` AS `table_name`',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    public function testSQLServerPrefixes(): void
    {
        $quoter = $this->makeQuoter('p_', '[]');

        $this->assertEquals(
            '*',
            $quoter->quote('*')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '[column]',
            $quoter->quote('column')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '[p_table].[column]',
            $quoter->quote('table.column')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '[p_table].*',
            $quoter->quote('table.*')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '[p_table_name]',
            $quoter->quote('table_name', true)
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '[p_table].[column] AS [column_alias]',
            $quoter->quote('table.column AS column_alias')
        );

        $quoter = clone $quoter;
        $this->assertEquals(
            '[p_table_name] AS [table_name]',
            $quoter->quote('table_name AS table_name', true)
        );
    }

    /**
     * @param string $prefix
     * @param string $quote
     * @return Quoter
     */
    protected function makeQuoter($prefix = '', string $quote = '""')
    {
        return new Quoter($prefix, $quote);
    }
}
