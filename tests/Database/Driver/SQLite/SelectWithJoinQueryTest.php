<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\SQLite;

/**
 * @group driver
 * @group driver-sqlite
 */
class SelectWithJoinQueryTest extends \Cycle\Database\Tests\SelectWithJoinQueryTest
{
    public const DRIVER = 'sqlite';
}
