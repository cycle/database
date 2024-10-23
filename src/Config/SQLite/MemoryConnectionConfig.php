<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\SQLite;

class MemoryConnectionConfig extends FileConnectionConfig
{
    /**
     * @var non-empty-string
     */
    protected const DATABASE_NAME = ':memory:';

    public function __construct(array $options = [])
    {
        parent::__construct(self::DATABASE_NAME, $options);
    }
}
