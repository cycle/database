<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config;

/**
 * List of connection examples:
 *  - MS SQL   - dblib:host=localhost:3007;dbname=dbname
 *  - MS SQL   - sqlsrv:Server=localhost,1521;Database=dbname
 *  - CUBRID   - cubrid:dbname=dbname;host=localhost;port=33000
 *  - MySQL    - mysql:host=localhost;port=3307;dbname=dbname
 *  - MySQL    - mysql:unix_socket=/tmp/mysql.sock;dbname=dbname
 *  - Firebird - firebird:dbname=localhost:/path/to/file.eu3
 *  - Firebird - firebird:dbname=localhost:example;charset=utf8;
 *  - IBM      - ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=dbname;HOSTNAME=localhost;
 *               PORT=56789;PROTOCOL=TCPIP;UID=user;PWD=pass
 *  - Informix - informix:host=localhost;service=9800;database=dbname;server=ids_server;
 *               protocol=onsoctcp;EnableScrollableCursors=1
 *  - Oracle   - oci:dbname=//localhost:1521/dbname
 *  - Oracle   - oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))
 *               (CONNECT_DATA=(SERVICE_NAME=ORCL)(SID=ORCL)))
 *  - ODBC/DB2 - odbc:DRIVER={IBM DB2 ODBC DRIVER};HOSTNAME=localhost;PORT=50000;DATABASE=dbname;
 *               PROTOCOL=TCPIP;UID=db2inst1;PWD=ibmdb2;
 *  - Postgres - pgsql:host=localhost;port=5432;dbname=dbname;user=login;password=pass
 *  - SQLite   - sqlite:/path/to/database.db
 *  - SQLite   - sqlite::memory:
 *  - SQLite   - sqlite:
 *
 * @psalm-type PDOFlag = \PDO::ATTR_*
 */
abstract class PDOConnectionConfig extends ConnectionConfig
{
    /**
     * General driver specific PDO options.
     *
     * @var array<PDOFlag, mixed>
     */
    protected const DEFAULT_PDO_OPTIONS = [
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * @param non-empty-string|null $user
     * @param non-empty-string|null $password
     * @param array<PDOFlag, mixed> $options
     */
    public function __construct(
        ?string $user = null,
        ?string $password = null,
        public array $options = [],
    ) {
        parent::__construct($user, $password);

        $this->options = \array_replace(static::DEFAULT_PDO_OPTIONS, $this->options);
    }

    /**
     * @return non-empty-string
     */
    abstract public function getName(): string;

    /**
     * @return array<PDOFlag, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns PDO data source name.
     *
     */
    abstract public function getDsn(): string;

    /**
     * @param iterable<int, mixed|non-empty-string> ...$fields
     *
     */
    protected function dsn(iterable $fields): string
    {
        $result = [];

        foreach ($fields as $key => $value) {
            if ($value === null) {
                continue;
            }

            $result[] = \is_string($key)
                ? \sprintf('%s=%s', $key, $this->dsnValueToString($value))
                : $this->dsnValueToString($value);
        }

        return \implode(';', $result);
    }

    private function dsnValueToString(mixed $value): string
    {
        return match (true) {
            \is_bool($value) => $value ? '1' : '0',
            // TODO Think about escaping special chars in strings
            \is_scalar($value), $value instanceof \Stringable => (string) $value,
            default => throw new \InvalidArgumentException(
                \sprintf('Can not convert config value of type "%s" to string', \get_debug_type($value)),
            ),
        };
    }
}
