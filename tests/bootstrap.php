<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    'sqlite'    => new Database\Config\SQLiteDriverCreateInfo(
        queryCache: true,
    ),
    'mysql'     => new Database\Config\MySQLDriverCreateInfo(
        connection: new Database\Config\MySQL\MySQLPDOUriConnectionInfo(
            database: 'spiral',
            host: '127.0.0.1',
            port: 13306,
            user: 'root',
            password: 'root',
        ),
        queryCache: true
    ),
    'postgres' => new Database\Config\PostgresDriverCreateInfo(
        connection: new Database\Config\Postgres\PostgresPDOConnectionInfo(
            database: 'spiral',
            host: '127.0.0.1',
            port: 15432,
            user: 'postgres',
            password: 'postgres',
        ),
        queryCache: true
    ),
    'sqlserver' => new Database\Config\SQLServerDriverCreateInfo(
        connection: new Database\Config\SQLServer\SQLServerPDOConnectionInfo(
            database: 'tempdb',
            host: '127.0.0.1',
            port: 11433,
            user: 'SA',
            password: 'SSpaSS__1'
        )
    ),
];

$db = getenv('DB') ?: null;
Database\Tests\BaseTest::$config = [
        'debug' => getenv('DB_DEBUG') ?: false,
    ] + ($db === null
        ? $drivers
        : array_intersect_key($drivers, array_flip((array)$db))
    );
