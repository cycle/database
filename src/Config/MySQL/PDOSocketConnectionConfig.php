<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\MySQL;

final class PDOSocketConnectionConfig extends PDOConnectionConfig
{
    /**
     * @param non-empty-string $socket
     * @param non-empty-string $database
     * @param non-empty-string|null $charset
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     * @param array<non-empty-string|int, non-empty-string> $options
     */
    public function __construct(
        string $database,
        public string $socket,
        ?string $user = null,
        ?string $password = null,
        ?string $charset = null,
        array $options = []
    ) {
        parent::__construct($database, $charset, $user, $password, $options);
    }

    /**
     * Returns the MySQL-specific PDO DSN with connection Unix socket,
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
