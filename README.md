Spiral DBAL
========
[![Latest Stable Version](https://poser.pugx.org/spiral/database/v/stable)](https://packagist.org/packages/spiral/database) 
[![Build Status](https://travis-ci.org/spiral/database.svg?branch=master)](https://travis-ci.org/spiral/database)
[![Codecov](https://codecov.io/gh/spiral/database/branch/master/graph/badge.svg)](https://codecov.io/gh/spiral/database/)

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
    'default'     => 'default',
    'databases'   => [
        'default' => [
            'connection' => 'sqlite',
        ],
    ],
    'connections' => [
        'sqlite' => [
            'driver'     => SQLiteDriver::class,
            'connection' => 'sqlite:database.db',
        ],
    ],
]));

// create or update table schema
$users = $dbm->database('default')->table('users')->getSchema();
$users->primary('id');
$users->string('name');
$users->datetime('created_at');
$users->datetime('updated_at');
$users->save();

// insert data
$dbm->database()->table('users')->insertOne([
    'name'       => 'test',
    'created_at' => new DateTimeImmutable(),
    'updated_at' => new DateTimeImmutable(),  
]);

// select data
foreach ($dbm->database()->select()->from('users') as $u) {
    print_r($u);
}
```
