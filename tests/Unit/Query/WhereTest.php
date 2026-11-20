<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query;

use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\BuilderException;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Exception\CompilerException\UnexpectedOperatorException;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class WhereTest
{
    private Database $db;

    #[BeforeTest]
    public function connect(): void
    {
        $this->db = new Database('default', '', SQLiteDriver::create(new SQLiteDriverConfig(queryCache: false)));
    }

    public function ignoresAWhereWithoutArguments(): void
    {
        $query = $this->db->select()->from('t')->where()->where(null);

        Assert::same($query->sqlStatement(), "SELECT *\nFROM \"t\"");
    }

    public function rejectsASingleScalarCondition(): never
    {
        Expect::exception(BuilderException::class)->withMessage('Expected array where or closure');

        $this->db->select()->from('t')->where('a');
    }

    public function castsAScalarOperatorToAString(): void
    {
        $query = $this->db->select()->from('t')->where('a', 5, 1);

        Assert::same($query->sqlStatement(), "SELECT *\nFROM \"t\"\nWHERE \"a\" 5 ? ");
    }

    public function rejectsANonStringOperatorBeforeTwoValues(): never
    {
        Expect::exception(BuilderException::class)->withMessage('Invalid operator type, string expected');

        $this->db->select()->from('t')->where('a', 1, 2, 3);
    }

    public function rejectsMoreThanFourArguments(): never
    {
        Expect::exception(BuilderException::class)->withMessage('Invalid where method call');

        $this->db->select()->from('t')->where('a', 'between', 1, 2, 3);
    }

    public function refusesToCompileAnOperatorThatIsNeitherAStringNorAFragment(): never
    {
        $query = $this->db->select()->from('t')->where('a', ['='], 1);

        Expect::exception(CompilerException::class)->withMessage('Invalid operator type, string or fragment is expected');

        $query->sqlStatement();
    }

    public function refusesToCompileASequenceAfterAComparisonOperator(): never
    {
        $query = $this->db->select()->from('t')->where('a', '>', new Parameter([1, 2]));

        Expect::exception(UnexpectedOperatorException::class)->withMessage(
            'Unable to compile query, unexpected operator `>` provided for a value sequence. '
            . 'Allowed operators: `IN`, `NOT IN` (or `=`, `!=` as sugar).',
        );

        $query->sqlStatement();
    }

    public function compilesAFullJoin(): void
    {
        $query = $this->db->select()->from('t')->fullJoin('u', 'x')->on('x.id', 't.id');

        Assert::same(
            $query->sqlStatement(),
            "SELECT *\nFROM \"t\" \nFULL JOIN \"u\" AS \"x\"\n    ON \"x\".\"id\" = \"t\".\"id\"  ",
        );
    }

    public function refusesAJsonConditionWithoutADriverSpecificBuilder(): never
    {
        Expect::exception(BuilderException::class)
            ->withMessage("This database engine can't handle the `whereJson` method.");

        (new SelectQuery())->whereJson('a->b', 1);
    }
}
