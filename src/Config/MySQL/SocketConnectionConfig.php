<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\MySQL;

use Cycle\Database\Config\ProvidesSourceString;

class SocketConnectionConfig extends ConnectionConfig implements ProvidesSourceString
{
    /**
     * @param non-empty-string $socket
     * @param non-empty-string $database
     * @param non-empty-string|null $charset
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     * @param array<int, non-empty-string|non-empty-string> $options
     */
    public function __construct(
        public string $database,
        public string $socket,
        public ?string $charset = null,
        ?string $user = null,
        ?string $password = null,
        array $options = [],
    ) {
        parent::__construct($user, $password, $options);
    }

    public function getSourceString(): string
    {
        return $this->database;
    }

    /**
     * Returns the MySQL-specific PDO DataSourceName with connection Unix socket,
     * that looks like:
     * <code>
     *  mysql:unix_socket=/tmp/mysql.sock;dbname=dbname
     * </code>
     *
     * {@inheritDoc}
     */
    public function getDsn(): string
    {
        $config = [
            'unix_socket' => $this->socket,
            'dbname' => $this->database,
            'charset' => $this->charset,
        ];

        return \sprintf('%s:%s', $this->getName(), $this->dsn($config));
    }
}
