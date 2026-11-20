<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Query;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Feature\Common\Query\ExceptionsTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class ExceptionsTest extends CommonClass
{
    #[Test]
    public function testExclusionViolationIsAConstrainException(): void
    {
        $driver = $this->database->getDriver();

        // A range column keeps the exclusion to the built-in gist opclasses; an `id WITH =` would
        // need btree_gist installed.
        $driver->execute('CREATE TABLE test (id int, span int4range, EXCLUDE USING gist (span WITH &&))');
        $driver->execute("INSERT INTO test VALUES (1, '[1,10)')");

        Expect::exception(StatementException\ConstrainException::class);

        $driver->execute("INSERT INTO test VALUES (2, '[5,15)')");
    }
}
