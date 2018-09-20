<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\MySQL;

class DefaultValueTest extends \Spiral\Database\Tests\DefaultValueTest
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