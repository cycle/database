<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\SQLite;

use Cycle\Database\Config\PDOConnectionConfig;

abstract class ConnectionConfig extends PDOConnectionConfig
{
    /**
     * @param array $options
     */
    public function __construct(
        array $options = []
    ) {
        parent::__construct(null, null, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'sqlite';
    }
}
