<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests;

use Cycle\Database\Database;
use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Exception\StatementException;

/**
 * Add exception versions in a future versions.
 */
abstract class ExceptionsTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp(): void
    {
        $this->database = $this->db();
    }

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
        $schema = $this->getDriver()->getSchema('test');
        $schema->primary('id');
        $schema->string('value')->nullable(false)->defaultValue(null);
        $schema->save();

        $this->getDriver()->insertQuery('', 'test')->values(['value' => 'value'])->run();

        try {
            $this->getDriver()->insertQuery('', 'test')->values(['value' => null])->run();
        } catch (StatementException\ConstrainException $e) {
            $this->assertInstanceOf(StatementException\ConstrainException::class, $e);
        }
    }
}
