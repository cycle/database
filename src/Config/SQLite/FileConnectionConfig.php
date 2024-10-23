<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\SQLite;

use Cycle\Database\Config\ProvidesSourceString;

class FileConnectionConfig extends ConnectionConfig implements ProvidesSourceString
{
    /**
     * @param string $database The pathname to the SQLite database file.
     *        - In case of keyword ":memory:" {@see MemoryConnectionConfig}.
     *        - In case of empty string value {@see TempFileConnectionConfig}.
     */
    public function __construct(
        public string $database = '',
        array $options = [],
    ) {
        parent::__construct($options);
    }

    /**
     * Returns the SQLite-specific PDO DataSourceName, that looks like:
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

    public function getSourceString(): string
    {
        return $this->database;
    }
}
