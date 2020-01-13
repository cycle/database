<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\MySQL;

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

    /**
     * @expectedException \Spiral\Database\Driver\MySQL\Exception\MySQLException
     * @expectedExceptionMessage Column table.target of type text/blob can not have non empty
     *                           default value
     */
    public function testTextDefaultValueEmpty(): void
    {
        parent::testTextDefaultValueEmpty();
    }
}
