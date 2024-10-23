<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\Postgres;

use Cycle\Database\Config\PDOConnectionConfig as BaseConnectionConfig;

abstract class ConnectionConfig extends BaseConnectionConfig
{
    /**
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     */
    public function __construct(
        ?string $user = null,
        ?string $password = null,
        array $options = [],
    ) {
        parent::__construct($user, $password, $options);
    }

    public function getName(): string
    {
        return 'pgsql';
    }
}
