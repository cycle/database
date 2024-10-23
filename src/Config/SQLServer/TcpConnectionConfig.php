<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\SQLServer;

use Cycle\Database\Config\ProvidesSourceString;
use Cycle\Database\Config\PDOConnectionConfig;

/**
 * @psalm-type IsolationLevelType = \PDO::SQLSRV_TXN_*
 *
 * @psalm-import-type PDOFlag from PDOConnectionConfig
 */
class TcpConnectionConfig extends ConnectionConfig implements ProvidesSourceString
{
    /**
     * @var ?positive-int
     */
    public ?int $port = null;

    /**
     * @param non-empty-string $database The name of the database.
     * @param non-empty-string $host Database connection host.
     * @param numeric-string|positive-int|null $port Database connection port.
     * @param non-empty-string|null $app The application name used in tracing.
     * @param bool|null $pooling Specifies whether the connection is assigned from a
     *        connection pool ({@see true}) or not ({@see false}).
     * @param bool|null $encrypt Specifies whether the communication with SQL Server
     *        is encrypted ({@see true}) or unencrypted ({@see false}).
     * @param non-empty-string|null $failover Specifies the server and instance of the database's
     *        mirror (if enabled and configured) to use when the primary server is unavailable.
     * @param int|null $timeout Specifies the number of seconds to wait before failing the connection attempt.
     * @param bool|null $mars Disables or explicitly enables support for Multiple Active Result Sets (MARS).
     * @param bool|null $quoted Specifies whether to use SQL-92 rules for quoted
     *        identifiers ({@see true}) or to use legacy Transact-SQL rules ({@see false}).
     * @param non-empty-string|null $traceFile Specifies the path for the file used for trace data.
     * @param bool|null $trace Specifies whether ODBC tracing is enabled ({@see true}) or
     *        disabled ({@see false}) for the connection being established.
     * @param IsolationLevelType|null $isolation Specifies the transaction isolation level.
     *        The accepted values for this option are:
     *          - {@see \PDO::SQLSRV_TXN_READ_UNCOMMITTED}
     *          - {@see \PDO::SQLSRV_TXN_READ_COMMITTED}
     *          - {@see \PDO::SQLSRV_TXN_REPEATABLE_READ}
     *          - {@see \PDO::SQLSRV_TXN_SNAPSHOT}
     *          - {@see \PDO::SQLSRV_TXN_SERIALIZABLE}
     * @param bool|null $trustServerCertificate Specifies whether the client should
     *        trust ({@see true}) or reject ({@see false}) a self-signed server certificate.
     * @param non-empty-string|null $wsid Specifies the name of the computer for tracing.
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     */
    public function __construct(
        public string $database,
        public string $host = 'localhost',
        int|string|null $port = null,
        public ?string $app = null,
        public ?bool $pooling = null,
        public ?bool $encrypt = null,
        public ?string $failover = null,
        public ?int $timeout = null,
        public ?bool $mars = null,
        public ?bool $quoted = null,
        public ?string $traceFile = null,
        public ?bool $trace = null,
        public ?int $isolation = null,
        public ?bool $trustServerCertificate = null,
        public ?string $wsid = null,
        ?string $user = null,
        ?string $password = null,
        array $options = [],
    ) {
        $this->port = $port !== null ? (int) $port : null;

        parent::__construct($user, $password, $options);
    }

    public function getSourceString(): string
    {
        return $this->database;
    }

    /**
     * Returns the SQL Server specific PDO DataSourceName, that looks like:
     * <code>
     *  sqlsrv:Server=localhost,1521;Database=dbname
     * </code>
     *
     * {@inheritDoc}
     */
    public function getDsn(): string
    {
        $config = [
            'APP' => $this->app,
            'ConnectionPooling' => $this->pooling,
            'Database' => $this->database,
            'Encrypt' => $this->encrypt,
            'Failover_Partner' => $this->failover,
            'LoginTimeout' => $this->timeout,
            'MultipleActiveResultSets' => $this->mars,
            'QuotedId' => $this->quoted,
            'Server' => \implode(',', [$this->host, $this->port]),
            'TraceFile' => $this->traceFile,
            'TraceOn' => $this->trace,
            'TransactionIsolation' => $this->isolation,
            'TrustServerCertificate' => $this->trustServerCertificate,
            'WSID' => $this->wsid,
        ];

        return \sprintf('%s:%s', $this->getName(), $this->dsn($config));
    }
}
