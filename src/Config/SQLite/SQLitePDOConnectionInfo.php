<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\SQLite;

use Cycle\Database\Config\PDOConnectionInfo;
use Cycle\Database\Config\ProvidesSourceString;

class SQLitePDOConnectionInfo extends PDOConnectionInfo implements ProvidesSourceString
{
    /**
     * @param string $database
     * @param array $options
     */
    public function __construct(
        public string $database,
        array $options = []
    ) {
        parent::__construct(null, null, $options);
    }

    /**
     * Returns the SQLite-specific PDO DSN, that looks like:
     * <code>
     *  sqlite:/path/to/database.db
     * </code>
     *
     * {@inheritDoc}
     */
    public function getDsn(): string
    {
        return \sprintf('%s:%s', $this->getName(), $this->database);
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceString(): string
    {
        return $this->database;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'sqlite';
    }
}
