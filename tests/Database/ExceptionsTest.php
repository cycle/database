<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Exception\HandlerException;
use Spiral\Database\Exception\StatementException;

/**
 * Add exception versions in a future versions.
 */
abstract class ExceptionsTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->db();
    }

    public function testSelectionException()
    {
        $select = $this->database->select()->from('udnefinedTable');
        try {
            $select->run();
        } catch (StatementException $e) {
            $this->assertInstanceOf(\PDOException::class, $e->pdoException());
            $this->assertInstanceOf(\PDOException::class, $e->getPrevious());

            $this->assertSame(
                $e->getQuery(),
                $select->sqlStatement()
            );
        }
    }

    public function testHandlerException()
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

    public function testInsertNotNullable()
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