<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

use Cycle\Database\Exception\StatementException;
// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\ExceptionsTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class ExceptionsTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testExclusionViolationIsAConstrainException(): void
    {
        $driver = $this->database->getDriver();

        // A range column keeps the exclusion to the built-in gist opclasses; an `id WITH =` would
        // need btree_gist installed.
        $driver->execute('CREATE TABLE test (id int, span int4range, EXCLUDE USING gist (span WITH &&))');
        $driver->execute("INSERT INTO test VALUES (1, '[1,10)')");

        \Testo\Expect::exception(StatementException\ConstrainException::class);

        $driver->execute("INSERT INTO test VALUES (2, '[5,15)')");
    }
}
