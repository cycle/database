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
class DeleteQueryTest extends \Cycle\Database\Tests\DeleteQueryTest
{
    public const DRIVER = 'postgres';
}
