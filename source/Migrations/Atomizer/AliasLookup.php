<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Migrations\Atomizer;

use Spiral\Atomizer\Exceptions\AtomizerException;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Schemas\Prototypes\AbstractTable;

/**
 * Provides ability to identify database and local table name based on given AbstractTable schema.
 * Utilizes DatabaseManager.
 */
class AliasLookup
{
    /**
     * @var DatabaseManager
     */
    private $dbal;

    /**
     * @param DatabaseManager $dbal
     */
    public function __construct(DatabaseManager $dbal)
    {
        $this->dbal = $dbal;
    }

    /**
     * Local database alias (no prefix included).
     *
     * @param AbstractTable $table
     *
     * @return string
     */
    public function tableAlias(AbstractTable $table): string
    {
        return substr($table->getName(), strlen($table->getPrefix()));
    }

    /**
     * Database associated with given table schema.
     *
     * @param AbstractTable $table
     *
     * @return string
     */
    public function databaseAlias(AbstractTable $table): string
    {
        foreach ($this->dbal->getDatabases() as $database) {
            if (
                $table->getDriver() == $database->getDriver()
                && $table->getPrefix() == $database->getPrefix()
            ) {
                return $database->getName();
            }
        }

        throw new AtomizerException("Unable to find database associated with {$table}");
    }
}