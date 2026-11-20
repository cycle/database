<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Query;

use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Exception\StatementException;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

/**
 * Add exception versions in a future versions.
 */
#[ExceptDriver(Driver::MySQL, Driver::Postgres)]
class ExceptionsTest extends DatabaseTestCase
{
    #[Test]
    public function testSelectionException(): void
    {
        $select = $this->database->select()->from('udnefinedTable');
        try {
            $select->run();
        } catch (StatementException $e) {
            Assert::instanceOf($e->getPrevious(), \PDOException::class);

            Assert::same($select->sqlStatement(), $e->getQuery());
        }
    }

    #[Test]
    public function testHandlerException(): void
    {
        $select = $this->database->select()->from('udnefinedTable');
        try {
            $select->run();
        } catch (StatementException $e) {
            $h = new HandlerException($e);

            Assert::instanceOf($h->getPrevious(), StatementException::class);

            Assert::same($select->sqlStatement(), $h->getQuery());
        }
    }

    #[Test]
    public function testInsertNotNullable(): void
    {
        $schema = $this->database->getDriver()->getSchema('test');
        $schema->primary('id');
        $schema->string('value')->nullable(false)->defaultValue(null);
        $schema->save();

        $this->database->getDriver()
            ->insertQuery('', 'test')
            ->values(['value' => 'value'])
            ->run();

        try {
            $this->database->getDriver()
                ->insertQuery('', 'test')
                ->values(['value' => null])
                ->run();
        } catch (StatementException\ConstrainException $e) {
            Assert::instanceOf($e, StatementException\ConstrainException::class);
        }
    }

    #[Test]
    public function testConstrainExceptionWhenTheValueLooksLikeAConnectionError(): void
    {
        $schema = $this->database->getDriver()->getSchema('test');
        $schema->primary('id');
        $schema->string('value', 64);
        $schema->index(['value'])->unique(true);
        $schema->save();

        // Every server but SQLite echoes the rejected value back in its error message, and `0800`
        // inside this uuid reads as the SQLSTATE class of a dropped connection.
        $value = 'f5a31835-fae5-43eb-8efa-cce00b90800a';

        $this->database->getDriver()
            ->insertQuery('', 'test')
            ->values(['value' => $value])
            ->run();

        Expect::exception(StatementException\ConstrainException::class);

        $this->database->getDriver()
            ->insertQuery('', 'test')
            ->values(['value' => $value])
            ->run();
    }
}
