<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

/**
 * @group driver
 * @group driver-postgres
 */
class ReadonlyTest extends \Cycle\Database\Tests\ReadonlyTest
{
    public const DRIVER = 'postgres';
}
