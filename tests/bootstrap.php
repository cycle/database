<?php

declare(strict_types=1);

use Cycle\Database;

// phpcs:disable
define('SPIRAL_INITIAL_TIME', microtime(true));

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
mb_internal_encoding('UTF-8');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

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
    'postgres_custom_pdo_options' => new Database\Config\PostgresDriverConfig(
        connection: new Database\Config\Postgres\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 15432,
            user: 'postgres',
            password: 'postgres',
            options: [
                /**
                 * Native PostgreSQL prepared statements are very permissive
                 * when it comes to data types, especially booleans.
                 * Emulating prepares on PHP side will help us catch bugs with data types
                 */
                PDO::ATTR_EMULATE_PREPARES => true,
                /**
                 * Stringify fetches will return everything as string,
                 * so e.g. decimal/numeric type will not be converted to float, thus losing the precision
                 * and letting users handle it differently.
                 *
                 * As a result, int is also returned as string, so we need to make sure
                 * that we're properly casting schema information details.
                 */
                PDO::ATTR_STRINGIFY_FETCHES => true,
            ],
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
];

$db = getenv('DB') ?: null;
Database\Tests\Functional\Driver\Common\BaseTest::$config = [
    'debug' => getenv('DB_DEBUG') ?: false,
] + (
    $db === null
    ? $drivers
    : array_intersect_key($drivers, array_flip((array)$db))
);
