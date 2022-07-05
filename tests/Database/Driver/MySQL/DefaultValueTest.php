<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\MySQL;

/**
 * @group driver
 * @group driver-mysql
 */
class DefaultValueTest extends \Cycle\Database\Tests\DefaultValueTest
{
    public const DRIVER = 'mysql';

    public function testTextDefaultValueString(): void
    {
        $this->expectException(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class);
        $this->expectExceptionMessage('Column table.target of type text/blob can not have non empty default value');
        parent::testTextDefaultValueString();
    }

    public function testTextDefaultValueEmpty(): void
    {
        $this->expectException(\Cycle\Database\Driver\MySQL\Exception\MySQLException::class);
        $this->expectExceptionMessage('Column table.target of type text/blob can not have non empty default value');
        parent::testTextDefaultValueEmpty();
    }
}
