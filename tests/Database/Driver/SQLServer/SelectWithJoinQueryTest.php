<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\SQLServer;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SelectWithJoinQueryTest extends \Cycle\Database\Tests\SelectWithJoinQueryTest
{
    public const DRIVER = 'sqlserver';
}
