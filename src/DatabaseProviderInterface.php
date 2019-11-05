<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database;

use Spiral\Database\Exception\DBALException;

interface DatabaseProviderInterface
{
    /**
     * Get Database associated with a given database alias or automatically created one.
     *
     * @param string|null $database
     * @return DatabaseInterface
     *
     * @throws DBALException
     */
    public function database(string $database = null): DatabaseInterface;
}
