<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Database;
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
        $this->database = $this->database();
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
}