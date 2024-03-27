<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * Add exception versions in a future versions.
 */
abstract class ExceptionsTest extends BaseTest
{
    public function testSelectionException(): void
    {
        $select = $this->database->select()->from('udnefinedTable');

        try {
            $select->run();
        } catch (StatementException $e) {
            $this->assertInstanceOf(\PDOException::class, $e->getPrevious());

            $this->assertSame(
                $e->getQuery(),
                $select->sqlStatement()
            );
        }
    }

    public function testHandlerException(): void
    {
        $select = $this->database->select()->from('udnefinedTable');

        try {
            $select->run();
        } catch (StatementException $e) {
            $h = new HandlerException($e);

            $this->assertInstanceOf(StatementException::class, $h->getPrevious());

            $this->assertSame(
                $h->getQuery(),
                $select->sqlStatement()
            );
        }
    }

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
            $this->assertInstanceOf(StatementException\ConstrainException::class, $e);
        }
    }
}
