# Cycle DBAL

[![Latest Stable Version](https://poser.pugx.org/cycle/database/v/stable)](https://packagist.org/packages/cycle/database) 
[![Build Status](https://github.com/cycle/database/workflows/build/badge.svg)](https://github.com/cycle/database/actions)
[![Codecov](https://codecov.io/gh/cycle/database/branch/master/graph/badge.svg)](https://codecov.io/gh/cycle/database/)

Secure, multiple SQL dialects (MySQL, PostgreSQL, SQLite, SQLServer), schema introspection, schema declaration, smart identifier wrappers, database partitions, query builders, nested queries.

## Documentation

* [Installation and Configuration](https://spiral.dev/docs/database-configuration)
* [Access Database](https://spiral.dev/docs/database-access)
* [Database Isolation](https://spiral.dev/docs/database-isolation)
* [Query Builders](https://spiral.dev/docs/database-query-builders)
* [Transactions](https://spiral.dev/docs/database-transactions)
* [Schema Introspection](https://spiral.dev/docs/database-introspection)
* [Schema Declaration](https://spiral.dev/docs/database-declaration)
* [Migrations](https://spiral.dev/docs/database-migrations)
* [Errata](https://spiral.dev/docs/database-errata)

## Requirements

Make sure that your server is configured with following PHP version and extensions:
* PHP 8.0+
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

use Cycle\Database\Config;
use Cycle\Database\DatabaseManager;

$dbm = new DatabaseManager(new Config\DatabaseConfig([
    'databases'   => [
        'default' => ['connection' => 'sqlite'],
    ],
    'connections' => [
        'sqlite' => new Config\SQLiteDriverConfig(
            connection: new Config\SQLite\FileConnectionConfig(
                database: 'runtime/database.db'
            ),
        ),
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

## License:

MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained by [Spiral Scout](https://spiralscout.com).
