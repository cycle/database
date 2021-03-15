<?php

/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

use Spiral\Database;

// phpcs:disable
define('SPIRAL_INITIAL_TIME', microtime(true));

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');
mb_internal_encoding('UTF-8');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$db = getenv('DB') ?: null;

$drivers = [
    'sqlite'    => [
        'driver'     => Database\Driver\SQLite\SQLiteDriver::class,
        'check'      => static function () use ($db) {
            return $db === 'sqlite' || in_array('sqlite', \PDO::getAvailableDrivers(), true);
        },
        'conn'       => 'sqlite::memory:',
        'user'       => 'sqlite',
        'pass'       => '',
        'queryCache' => 100
    ],
    'mysql'     => [
        'driver'     => Database\Driver\MySQL\MySQLDriver::class,
        'check'      => static function () use ($db) {
            return $db === 'mysql' || in_array('mysql', \PDO::getAvailableDrivers(), true);
        },
        'conn'       => 'mysql:host=127.0.0.1:13306;dbname=spiral',
        'user'       => 'root',
        'pass'       => 'root',
        'queryCache' => 100
    ],
    'postgres'  => [
        'driver'     => Database\Driver\Postgres\PostgresDriver::class,
        'check'      => static function () use ($db) {
            return $db === 'postgres' || in_array('pgsql', \PDO::getAvailableDrivers(), true);
        },
        'conn'       => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
        'user'       => 'postgres',
        'pass'       => 'postgres',
        'queryCache' => 100
    ],
    'sqlserver' => [
        'driver'     => Database\Driver\SQLServer\SQLServerDriver::class,
        'check'      => static function () use ($db) {
            return $db === 'sqlserver' || in_array('sqlsrv', \PDO::getAvailableDrivers(), true);
        },
        'conn'       => 'sqlsrv:Server=127.0.0.1,11433;Database=tempdb',
        'user'       => 'SA',
        'pass'       => 'SSpaSS__1',
        'queryCache' => 100
    ],
];

Database\Tests\BaseTest::$config = [
        'debug' => false,
    ] + ($db === null
        ? $drivers
        : array_intersect_key($drivers, array_flip((array)$db))
    );