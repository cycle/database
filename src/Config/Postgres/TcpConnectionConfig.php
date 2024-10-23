<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\Postgres;

use Cycle\Database\Config\ProvidesSourceString;

class TcpConnectionConfig extends ConnectionConfig implements ProvidesSourceString
{
    /**
     * @var positive-int
     */
    public int $port;

    /**
     * @param non-empty-string $database
     * @param non-empty-string $host
     * @param numeric-string|positive-int $port
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     */
    public function __construct(
        public string $database,
        public string $host = 'localhost',
        int|string $port = 5432,
        ?string $user = null,
        ?string $password = null,
        array $options = [],
    ) {
        $this->port = (int) $port;

        parent::__construct($user, $password, $options);
    }

    public function getSourceString(): string
    {
        return $this->database;
    }

    /**
     * Returns the Postgres-specific PDO DataSourceName, that looks like:
     * <code>
     *  pgsql:host=localhost;port=5432;dbname=dbname;user=login;password=pass
     * </code>
     *
     * {@inheritDoc}
     */
    public function getDsn(): string
    {
        $config = [
            'host' => $this->host,
            'port' => $this->port,
            'dbname' => $this->database,

            //
            // Username and Password may be is a part of DataSourceName
            // However, they can also be passed as separate
            // parameters, so we ignore the case with the DataSourceName:
            //
            // 'user'     => $this->user,
            // 'password' => $this->password,
        ];

        return \sprintf('%s:%s', $this->getName(), $this->dsn($config));
    }
}
