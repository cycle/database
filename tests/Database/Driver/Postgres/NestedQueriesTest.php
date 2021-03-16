<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\Postgres;

/**
 * @group driver
 * @group driver-postgres
 */
class NestedQueriesTest extends \Spiral\Database\Tests\NestedQueriesTest
{
    public const DRIVER = 'postgres';
}
