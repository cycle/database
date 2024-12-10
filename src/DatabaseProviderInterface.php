<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database;

use Cycle\Database\Exception\DBALException;

interface DatabaseProviderInterface
{
    /**
     * Get Database associated with a given database alias or automatically created one.
     *
     * @throws DBALException
     *
     */
    public function database(?string $database = null): DatabaseInterface;
}
