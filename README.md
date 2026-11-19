<div align="center">
    <a href="https://cycle-orm.dev" target="_blank">
        <picture>
            <source media="(prefers-color-scheme: dark)" srcset="https://github.com/cycle/.github/blob/main/logo/words-vector-dark.svg?raw=true">
            <img width="50%" align="center" src="https://github.com/cycle/.github/blob/main/logo/words-vector-light.svg?raw=true" alt="CycleORM Logo">
        </picture>
    </a>
</div>

<p align="center">Cycle DBAL: database abstraction layer, schema introspection, migrations and pagination</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://cycle-orm.dev/docs/database-configuration)
[![Sponsor](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsor&logo=githubsponsors&logoColor=white&color=%23EA4AAA)](https://github.com/sponsors/cycle)
[![Discord](https://img.shields.io/discord/538114875570913290?style=for-the-badge&logo=discord&labelColor=7289d9&logoColor=white&color=39456d)](https://discord.gg/spiralphp)

[![Psalm Level](https://shepherd.dev/github/cycle/database/level.svg)](https://shepherd.dev/github/cycle/database)
[![Type Coverage](https://shepherd.dev/github/cycle/database/coverage.svg)](https://shepherd.dev/github/cycle/database)
[![Codecov](https://codecov.io/gh/cycle/database/branch/2.x/graph/badge.svg)](https://codecov.io/gh/cycle/database)

</div>

<br />

Secure, multiple SQL dialects (MySQL, PostgreSQL, SQLite, SQLServer), schema introspection, schema declaration, smart identifier wrappers, database partitions, query builders, nested queries.

## Get Started

### Requirements

Make sure that your server is configured with following PHP version and extensions:

* PHP 8.2+
* PDO Extension with desired database drivers

### Installation

```bash
composer require cycle/database
```

[![PHP](https://img.shields.io/packagist/php-v/cycle/database.svg?style=flat-square&logo=php)](https://packagist.org/packages/cycle/database)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/cycle/database.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/cycle/database)
[![License](https://img.shields.io/packagist/l/cycle/database.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/cycle/database.svg?style=flat-square)](https://packagist.org/packages/cycle/database/stats)

### Example

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

## Documentation

* [Installation and Configuration](https://cycle-orm.dev/docs/database-configuration)
* [Access Database](https://cycle-orm.dev/docs/database-access)
* [Database Isolation](https://cycle-orm.dev/docs/database-isolation)
* [Query Builders](https://cycle-orm.dev/docs/database-query-builders)
* [Transactions](https://cycle-orm.dev/docs/database-transactions)
* [Schema Introspection](https://cycle-orm.dev/docs/database-introspection)
* [Schema Declaration](https://cycle-orm.dev/docs/database-declaration)
* [Migrations](https://cycle-orm.dev/docs/database-migrations)
* [Errata](https://cycle-orm.dev/docs/database-errata)
