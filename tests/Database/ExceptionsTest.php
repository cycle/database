<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use Spiral\Database\Database;
use Spiral\Database\Exception\HandlerException;
use Spiral\Database\Exception\QueryException;

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
        } catch (QueryException $e) {
            $this->assertInstanceOf(\PDOException::class, $e->pdoException());
            $this->assertInstanceOf(\PDOException::class, $e->getPrevious());

            $this->assertSame(
                $e->getQuery(),
                $select->queryString()
            );
        }
    }

    public function testHandlerException()
    {
        $select = $this->database->select()->from('udnefinedTable');
        try {
            $select->run();
        } catch (QueryException $e) {
            $h = new HandlerException($e);

            $this->assertInstanceOf(QueryException::class, $h->getPrevious());
            $this->assertSame(
                $h->getQuery(),
                $select->queryString()
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
        } catch (QueryException\ConstrainException $e) {
            $this->assertInstanceOf(QueryException\ConstrainException::class, $e);
        }
    }
}