Cycle DBAL
========
[![Latest Stable Version](https://poser.pugx.org/cycle/database/v/stable)](https://packagist.org/packages/cycle/database) 
[![Build Status](https://github.com/cycle/database/workflows/build/badge.svg)](https://github.com/cycle/database/actions)
[![Codecov](https://codecov.io/gh/cycle/database/branch/master/graph/badge.svg)](https://codecov.io/gh/cycle/database/)

Secure, multiple SQL dialects (MySQL, PostgreSQL, SQLite, SQLServer), schema introspection, schema declaration, smart identifier wrappers, database partitions, query builders, nested queries.

Documentation
--------
* [Installation and Configuration](https://cycle-orm.dev/docs/database-configuration/1.x)
* [Access Database](https://cycle-orm.dev/docs/database-access/1.x)
* [Database Isolation](https://cycle-orm.dev/docs/database-isolation/1.x)
* [Query Builders](https://cycle-orm.dev/docs/database-query-builders/1.x)
* [Transactions](https://cycle-orm.dev/docs/database-transactions/1.x)
* [Schema Introspection](https://cycle-orm.dev/docs/database-introspection/1.x)
* [Schema Declaration](https://cycle-orm.dev/docs/database-declaration/1.x)
* [Migrations](https://cycle-orm.dev/docs/database-migrations/1.x)
* [Errata](https://cycle-orm.dev/docs/database-errata/1.x)

Requirements
--------
Make sure that your server is configured with following PHP version and extensions:
* PHP 7.2+
* PDO Extension with desired database drivers

## Installation
To install the component:

```
$ composer require cycle/database
```

## Example
Given example demonstrates the connection to SQLite database, creation of table schema, data insertion and selection:

```php
<?php
declare(strict_types=1);

require_once "vendor/autoload.php";

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\SQLite\SQLiteDriver;

$dbm = new DatabaseManager(new DatabaseConfig([
    'databases'   => [
        'default' => ['connection' => 'sqlite'],
    ],
    'connections' => [
        'sqlite' => [
            'driver'     => SQLiteDriver::class,
            'connection' => 'sqlite:database.db',
        ],
    ],
]));

$users = $dbm->database('default')->table('users');

// create or update table schema
$schema = $users->getSchema();
$schema->primary('id');
$schema->string('name');
$schema->datetime('created_at');
$schema->datetime('updated_at');
$schema->save();

// insert data
$users->insertOne([
    'name'       => 'test',
    'created_at' => new DateTimeImmutable(),
    'updated_at' => new DateTimeImmutable(),  
]);

// select data
foreach ($users->select()->where(['name' => 'test']) as $u) {
    print_r($u);
}
```

License:
--------
MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by [Spiral Scout](https://spiralscout.com).
