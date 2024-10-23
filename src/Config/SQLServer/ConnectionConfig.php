<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\SQLServer;

use Cycle\Database\Config\PDOConnectionConfig;

/**
 * @psalm-type IsolationLevelType = \PDO::SQLSRV_TXN_*
 *
 * @psalm-import-type PDOFlag from PDOConnectionConfig
 */
abstract class ConnectionConfig extends PDOConnectionConfig
{
    /**
     * General driver specific PDO options.
     *
     * @var array<PDOFlag, mixed>
     */
    protected const DEFAULT_PDO_OPTIONS = [
        \PDO::ATTR_CASE              => \PDO::CASE_NATURAL,
        \PDO::ATTR_ERRMODE           => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

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
        return 'sqlsrv';
    }
}
