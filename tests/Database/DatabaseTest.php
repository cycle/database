<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\tests\Cases\Database;

use Mockery as m;
use Spiral\Database\Entity\Database;
use Spiral\Database\Entity\Driver;
use Spiral\Database\Entity\QueryStatement;
use Spiral\Database\Entity\Table;

class DatabaseTest extends \PHPUnit_Framework_TestCase
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

    public function testTable()
    {
        $driver = $this->makeDriver();

        $database = new Database($driver, 'test', 'prefix_');

        $driver->expects($this->once())->method('hasTable')->with('prefix_table')->will(
            $this->returnValue(true)
        );

        $this->assertInstanceOf(Table::class, $table = $database->table('table'));
        $this->assertEquals('table', $table->getName());
        $this->assertEquals('prefix_table', $table->fullName());

        $this->assertTrue($table->exists());
    }

    /**
     * @return Driver|\PHPUnit_Framework_MockObject_MockObject
     */
    private function makeDriver()
    {
        return $this->getMockBuilder(Driver::class)->disableOriginalConstructor()->getMock();
    }
}
