<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\MySQL;

final class MySQLPDOUriConnectionInfo extends MySQLPDOConnectionInfo
{
    /**
     * @param non-empty-string $host
     * @param positive-int $port
     * @param non-empty-string $database
     * @param non-empty-string|null $charset
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     * @param array<non-empty-string|int, non-empty-string> $options
     */
    public function __construct(
        string $database,
        public string $host = 'localhost',
        public int $port = 3307,
        ?string $charset = null,
        ?string $user = null,
        ?string $password = null,
        array $options = [],
    ) {
        parent::__construct($database, $charset, $user, $password, $options);
    }

    /**
     * Returns the MySQL-specific PDO DSN with connection URI,
     * that looks like:
     * <code>
     *  mysql:host=localhost;port=3307;dbname=dbname
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
            'charset' => $this->charset,
        ];

        return \sprintf('%s:%s', $this->getName(), $this->dsn($config));
    }
}
