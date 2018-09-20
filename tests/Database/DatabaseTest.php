<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\tests\Cases\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\Database;
use Spiral\Database\Driver\AbstractDriver;
use Spiral\Database\QueryStatement;

class DatabaseTest extends TestCase
{
    public function testDatabase()
    {
        $driver = $this->makeDriver();

        $driver->method('getType')->will($this->returnValue('test-driver'));

        $database = new Database($driver, 'test', 'prefix_');

        $this->assertEquals('test', $database->getName());
        $this->assertEquals($driver, $database->getDriver());
        $this->assertEquals('prefix_', $database->getPrefix());
        $this->assertEquals('test-driver', $database->getType());
    }

    public function testQuery()
    {
        $driver = $this->makeDriver();

        $driver->expects($this->once())->method('query')->with('test query')
            ->willReturn(m::mock(QueryStatement::class));

        $database = new Database($driver, 'test', 'prefix_');
        $database->query('test query');
    }

    public function testHasTable()
    {
        $driver = $this->makeDriver();

        $driver->expects($this->once())->method('hasTable')->with('prefix_table')->will(
            $this->returnValue(true)
        );

        $database = new Database($driver, 'test', 'prefix_');
        $this->assertTrue($database->hasTable('table'));
    }

    /**
     * @return AbstractDriver|\PHPUnit_Framework_MockObject_MockObject
     */
    private function makeDriver()
    {
        return $this->getMockBuilder(AbstractDriver::class)->disableOriginalConstructor()->getMock();
    }
}
