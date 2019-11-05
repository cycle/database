<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\MySQL;

class DefaultValueTest extends \Spiral\Database\Tests\DefaultValueTest
{
    public const DRIVER = 'mysql';

    /**
     * @expectedException \Spiral\Database\Driver\MySQL\Exception\MySQLException
     * @expectedExceptionMessage Column table.target of type text/blob can not have non empty
     *                           default value
     */
    public function testTextDefaultValueString(): void
    {
        parent::testTextDefaultValueString();
    }
}
