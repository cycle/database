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
use Spiral\Database\DatabaseProviderInterface as SpiralDatabaseProviderInterface;

interface DatabaseProviderInterface
{
    /**
     * Get Database associated with a given database alias or automatically created one.
     *
     * @param string|null $database
     *
     * @throws DBALException
     *
     * @return DatabaseInterface
     */
    public function database(string $database = null): DatabaseInterface;
}
\class_alias(DatabaseProviderInterface::class, SpiralDatabaseProviderInterface::class, false);
