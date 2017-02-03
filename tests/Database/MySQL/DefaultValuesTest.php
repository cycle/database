<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\MySQL;

class DefaultValuesTest extends \Spiral\Tests\Database\DefaultValuesTest
{
    use DriverTrait;

    /**
     * @expectedException \Spiral\Database\Exceptions\Drivers\MySQLDriverException
     * @expectedExceptionMessage Column table.target of type text/blob can not have non empty
     *                           default value
     */
    public function testTextDefaultValueString()
    {
        parent::testTextDefaultValueString();
    }
}