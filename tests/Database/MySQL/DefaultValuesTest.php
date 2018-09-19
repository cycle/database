<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Database\Tests\MySQL;

class DefaultValuesTest extends \Spiral\Database\Tests\DefaultValuesTest
{
    use DriverTrait;

    /**
     * @expectedException \Spiral\Database\Exception\Driver\MySQLDriverException
     * @expectedExceptionMessage Column table.target of type text/blob can not have non empty
     *                           default value
     */
    public function testTextDefaultValueString()
    {
        parent::testTextDefaultValueString();
    }
}