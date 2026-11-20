<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\Quoter;
use Cycle\Database\Exception\CompilerException;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class QuoterTest
{
    public function testPrefixless(): void
    {
        $quoter = $this->makeQuoter();

        Assert::equals($quoter->quote('*'), '*');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('column'), '"column"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column'), '"table"."column"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.*'), '"table".*');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name', true), '"table_name"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column AS column_alias'), '"table"."column" AS "column_alias"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name AS table_name', true), '"table_name" AS "table_name"');
    }

    public function testPrefixlessAggregations(): void
    {
        $quoter = $this->makeQuoter();

        Assert::equals($quoter->quote('COUNT(*)'), 'COUNT(*)');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('SUM(column)'), 'SUM("column")');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('MIN(table.column)'), 'MIN("table"."column")');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('AVG(table.column) AS column_alias'), 'AVG("table"."column") AS "column_alias"');
    }

    public function testPrefixlessOperations(): void
    {
        $quoter = $this->makeQuoter();

        Assert::equals($quoter->quote('column_a + column_b'), '"column_a" + "column_b"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column * 10'), '"table"."column" * 10');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('(table.column + some_column) / other_table.column_b'), '("table"."column" + "some_column") / "other_table"."column_b"');
    }

    public function testPrefixes(): void
    {
        $quoter = $this->makeQuoter('p_');

        Assert::equals($quoter->quote('*'), '*');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('column'), '"column"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column'), '"p_table"."column"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.*'), '"p_table".*');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name', true), '"p_table_name"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column AS column_alias'), '"p_table"."column" AS "column_alias"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name AS table_name', true), '"p_table_name" AS "table_name"');
    }

    public function testPrefixesAggregations(): void
    {
        $quoter = $this->makeQuoter('p_');

        Assert::equals($quoter->quote('COUNT(*)'), 'COUNT(*)');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('SUM(column)'), 'SUM("column")');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('MIN(table.column)'), 'MIN("p_table"."column")');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('AVG(table.column) AS column_alias'), 'AVG("p_table"."column") AS "column_alias"');
    }

    public function testPrefixesOperations(): void
    {
        $quoter = $this->makeQuoter('p_');

        Assert::equals($quoter->quote('column_a + column_b'), '"column_a" + "column_b"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column * 10'), '"p_table"."column" * 10');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('(table.column + some_column) / other_table.column_b AS xxx'), '("p_table"."column" + "some_column") / "p_other_table"."column_b" AS "xxx"');
    }

    public function testAliases(): void
    {
        $quoter = $this->makeQuoter('p_');

        Assert::equals($quoter->quote('table.column'), '"p_table"."column"');
        Assert::equals($quoter->quote('table_name', true), '"p_table_name"');

        Assert::equals($quoter->quote('table_name AS bubble', true), '"p_table_name" AS "bubble"');

        Assert::equals($quoter->quote('bubble.column AS column_alias'), '"bubble"."column" AS "column_alias"');

        Assert::equals($quoter->quote('table_name AS table_name', true), '"p_table_name" AS "table_name"');

        Assert::equals($quoter->quote('table_name.column'), '"table_name"."column"');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name.column'), '"p_table_name"."column"');

        Assert::equals($quoter->quote('bubble.column AS column_alias'), '"p_bubble"."column" AS "column_alias"');
    }

    public function testAliasesAggregations(): void
    {
        $quoter = $this->makeQuoter('p_');

        Assert::equals($quoter->quote('table_name AS bubble', true), '"p_table_name" AS "bubble"');

        Assert::equals($quoter->quote('MIN(bubble.column)'), 'MIN("bubble"."column")');

        Assert::equals($quoter->quote('AVG(bubble.column) AS column_alias'), 'AVG("bubble"."column") AS "column_alias"');
    }

    public function testAliasesOperations(): void
    {
        $quoter = $this->makeQuoter('p_');
        Assert::equals($quoter->quote('table_name AS bubble', true), '"p_table_name" AS "bubble"');

        Assert::equals($quoter->quote('bubble.column * 10 + other_table.column_x'), '"bubble"."column" * 10 + "p_other_table"."column_x"');

        Assert::equals($quoter->quote('(table.column + some_column) / yolo.column_b'), '("p_table"."column" + "some_column") / "p_yolo"."column_b"');

        Assert::equals($quoter->quote('(table.column + some_column) / bubble.column_b AS xxx'), '("p_table"."column" + "some_column") / "bubble"."column_b" AS "xxx"');
    }

    public function testCollisions(): void
    {
        $quoter = $this->makeQuoter('p_');

        Assert::equals($quoter->quote('table.column AS bubble'), '"p_table"."column" AS "bubble"');

        Assert::equals($quoter->quote('bubble', false), '"bubble"');
        Assert::equals($quoter->quote('bubble', true), '"p_bubble"');

        Assert::equals($quoter->quote('bubble.column AS new_bubble'), '"p_bubble"."column" AS "new_bubble"');

        Assert::equals($quoter->quote('new_bubble AS x_bubble', true), '"p_new_bubble" AS "x_bubble"');

        Assert::equals($quoter->quote('new_bubble', true), '"p_new_bubble"');
        Assert::equals($quoter->quote('x_bubble', true), '"x_bubble"');
    }

    public function testMySQLlPrefixes(): void
    {
        $quoter = $this->makeQuoter('p_', '``');

        Assert::equals($quoter->quote('*'), '*');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('column'), '`column`');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column'), '`p_table`.`column`');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.*'), '`p_table`.*');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name', true), '`p_table_name`');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column AS column_alias'), '`p_table`.`column` AS `column_alias`');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name AS table_name', true), '`p_table_name` AS `table_name`');
    }

    public function testSQLServerPrefixes(): void
    {
        $quoter = $this->makeQuoter('p_', '[]');

        Assert::equals($quoter->quote('*'), '*');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('column'), '[column]');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column'), '[p_table].[column]');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.*'), '[p_table].*');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name', true), '[p_table_name]');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table.column AS column_alias'), '[p_table].[column] AS [column_alias]');

        $quoter = clone $quoter;
        Assert::equals($quoter->quote('table_name AS table_name', true), '[p_table_name] AS [table_name]');
    }

    public function testEscapesQuotesInsideAnIdentifier(): void
    {
        Assert::same($this->makeQuoter('', '[]')->identifier('a[b]c.d'), '[a[[b]]c].[d]');
    }

    public function testDoesNotPrefixAPrefixedTableTwice(): void
    {
        $quoter = $this->makeQuoter('p_');
        $quoter->quote('users', true);

        Assert::same($quoter->quote('p_users', true), '"p_users"');
    }

    public function testRejectsQuotesOtherThanAPair(): never
    {
        Expect::exception(CompilerException::class)->withMessage('Invalid quoter quotes, expected 2 characters string');

        $this->makeQuoter('', '"');
    }

    protected function makeQuoter(string $prefix = '', string $quote = '""'): Quoter
    {
        return new Quoter($prefix, $quote);
    }
}
