<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver;

/**
 * Quote identifier and table names.
 */
interface QuoterInterface
{
    /**
     * Query query identifier, if identified stated as table - table prefix must be added.
     *
     * @param string $identifier Identifier can include simple column operations and functions, having "." in it will
     *                           automatically force table prefix to first value.
     * @param bool   $isTable    Set to true to let quote method know that identifier is related to table name.
     * @return mixed|string
     */
    public function quote(string $identifier, bool $isTable = false): string;
}