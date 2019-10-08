Spiral DBAL
========
[![Latest Stable Version](https://poser.pugx.org/spiral/database/v/stable)](https://packagist.org/packages/spiral/database) 
[![Build Status](https://travis-ci.org/spiral/database.svg?branch=master)](https://travis-ci.org/spiral/database)
[![Codecov](https://codecov.io/gh/spiral/database/branch/master/graph/badge.svg)](https://codecov.io/gh/spiral/database/)

Secure, multiple SQL dialects (MySQL, PostgreSQL, SQLite, SQLServer), schema introspection, schema declaration, smart identifier wrappers, database partitions, query builders, nested queries.

Documentation (v1.0.0 - outdated)
--------
* [Overview](https://github.com/spiral/docs/blob/master/database/overview.md)
* [Databases and Drivers](https://github.com/spiral/docs/blob/master/database/databases.md)
* [Query Builders](https://github.com/spiral/docs/blob/master/database/builders.md)
* [Transactions](https://github.com/spiral/docs/blob/master/database/transactions.md)
* [Schema Introspection](https://github.com/spiral/docs/blob/master/database/introspection.md)
* [Schema Declaration](https://github.com/spiral/docs/blob/master/database/declaration.md)
* [Migrations](https://github.com/spiral/docs/blob/master/database/migrations.md)
* [Errata](https://github.com/spiral/docs/blob/master/database/errata.md)

Requirements
--------
Make sure that your server is configured with following PHP version and extensions:
* PHP 7.2+
* PDO Extension with desired database drivers

## Installation
To install the component:

```
$ composer require spiral/database
```

## Example
Given example demonstrates the connection to SQLite database, creation of table schema, data insertion and selection:

```php
<?php
declare(strict_types=1);

require_once "vendor/autoload.php";

use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Driver\SQLite\SQLiteDriver;

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
