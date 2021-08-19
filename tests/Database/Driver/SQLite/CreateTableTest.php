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
class CreateTableTest extends \Cycle\Database\Tests\CreateTableTest
{
    public const DRIVER = 'sqlite';
}
