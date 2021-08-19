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
class ForeignKeysTest extends \Cycle\Database\Tests\ForeignKeysTest
{
    public const DRIVER = 'postgres';
}
