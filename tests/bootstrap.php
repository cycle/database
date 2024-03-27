<?php

declare(strict_types=1);

use Cycle\Database;

// phpcs:disable
define('SPIRAL_INITIAL_TIME', microtime(true));

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
mb_internal_encoding('UTF-8');

//Composer
require dirname(__DIR__).'/vendor/autoload.php';

$drivers = [
    'sqlite' => new Database\Config\SQLiteDriverConfig(
        queryCache: true,
    ),
    'mysql' => new Database\Config\MySQLDriverConfig(
        connection: new Database\Config\MySQL\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 13306,
            user: 'root',
            password: 'root',
        ),
        queryCache: true
    ),
    'postgres' => new Database\Config\PostgresDriverConfig(
        connection: new Database\Config\Postgres\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 15432,
            user: 'postgres',
            password: 'postgres',
        ),
        schema: 'public',
        queryCache: true,
    ),
    'sqlserver' => new Database\Config\SQLServerDriverConfig(
        connection: new Database\Config\SQLServer\TcpConnectionConfig(
            database: 'tempdb',
            host: '127.0.0.1',
            port: 11433,
            user: 'SA',
            password: 'SSpaSS__1'
        ),
        queryCache: true
    ),
    // Mocks
    'sqlite-mock' => new Database\Config\SQLiteDriverConfig(
        driver: Database\Tests\Stub\Driver\SQLiteWrapDriver::class,
        queryCache: true
    ),
    'postgres-mock' => new Database\Config\PostgresDriverConfig(
        connection: new Database\Config\Postgres\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 15432,
            user: 'postgres',
            password: 'postgres',
        ),
        schema: 'public',
        driver: Database\Tests\Stub\Driver\PostgresWrapDriver::class,
        queryCache: true
    ),
    'mysql-mock' => new Database\Config\MySQLDriverConfig(
        connection: new Database\Config\MySQL\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 13306,
            user: 'root',
            password: 'root',
        ),
        driver: Database\Tests\Stub\Driver\MysqlWrapDriver::class,
        queryCache: true,
    ),
    'sqlserver-mock' => new Database\Config\SQLServerDriverConfig(
        connection: new Database\Config\SQLServer\TcpConnectionConfig(
            database: 'tempdb',
            host: '127.0.0.1',
            port: 11433,
            user: 'SA',
            password: 'SSpaSS__1'
        ),
        driver: Database\Tests\Stub\Driver\MSSQLWrapDriver::class,
        queryCache: true,
    ),
];

$db = getenv('DB') ?: null;
if ($db !== null) {
    $db = [$db, "$db-mock"];
}
Database\Tests\Functional\Driver\Common\BaseTest::$config = [
    'debug' => getenv('DB_DEBUG') ?: false,
] + (
    $db === null
    ? $drivers
    : array_intersect_key($drivers, array_flip((array) $db))
);
