<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\MySQL;

class DefaultValuesTest extends \Spiral\Database\Tests\DefaultValuesTest
{
    const DRIVER = 'mysql';

    /**
     * @expectedException \Spiral\Database\Driver\MySQL\Exception\MySQLException
     * @expectedExceptionMessage Column table.target of type text/blob can not have non empty
     *                           default value
     */
    public function testTextDefaultValueString()
    {
        parent::testTextDefaultValueString();
    }
}