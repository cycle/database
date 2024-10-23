<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\SQLite;

class TempFileConnectionConfig extends FileConnectionConfig
{
    public function __construct(array $options = [])
    {
        /**
         * If an empty database string is passed, a temporary db file will be used.
         *
         * @see https://www.php.net/manual/en/ref.pdo-sqlite.connection.php
         */
        parent::__construct('', $options);
    }
}
