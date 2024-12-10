# Changelog

## [2.11.3](https://github.com/cycle/database/compare/2.11.2...2.11.3) (2024-12-10)


### Bug Fixes

* improve compatibility with PHP 8.4 ([#214](https://github.com/cycle/database/issues/214)) ([a6b860e](https://github.com/cycle/database/commit/a6b860e55c4e1f5812d9d176e659abeb0b4370cc))


### Styles

* apply Code Style to tests ([02c2437](https://github.com/cycle/database/commit/02c24376022c6b131df082b947a9dfdc5d12cbc0))
* **php-cs-fixer:** fix coding standards ([3822f76](https://github.com/cycle/database/commit/3822f7624d6d531b112fd4e5cf5d7f338fa6cb40))

## [2.11.2](https://github.com/cycle/database/compare/2.11.1...2.11.2) (2024-10-23)


### Styles

* apply new code style ([89a7c57](https://github.com/cycle/database/commit/89a7c574c5fe6538541065cf579defacf100c246))
* **php-cs-fixer:** fix coding standards ([1eae6db](https://github.com/cycle/database/commit/1eae6dbfa5cf7d27107daded67d9fae03ab70b28))


### Continuous Integration

* add Spiral CS fixer ([a72e1ae](https://github.com/cycle/database/commit/a72e1ae687eab6d4a3ec97f5fa7f58369d771227))
* normalize DB passwords in tests; fix style in cs-fix workflow ([b514009](https://github.com/cycle/database/commit/b514009e28adf149cc816e9f9b527e92b653ff73))
* use common MSSQL workflow ([5c434ec](https://github.com/cycle/database/commit/5c434ec9cb844e0569076f6bb1211e43c587f69d))
* use common MySQL workflow ([c5aa164](https://github.com/cycle/database/commit/c5aa1646911e99db51b9ec3aa1fb8e36087fb29d))
* use common Postgres workflow ([8c17e52](https://github.com/cycle/database/commit/8c17e520c84163572ffd1adb394d53558c86cc92))

## [2.11.1](https://github.com/cycle/database/compare/2.11.0...2.11.1) (2024-10-22)


### Bug Fixes

* typecasting of an integer to a boolean in SQLite ([32c29c7](https://github.com/cycle/database/commit/32c29c760ca30735b4d0f36b8ad11af9bb470997))


### Continuous Integration

* fix MSSQL initialization in CI ([678c049](https://github.com/cycle/database/commit/678c04970760f5be76e13dde9f84dd85d4465e37))
* replace `docker-compose` with `docker compose` ([914ed58](https://github.com/cycle/database/commit/914ed5837966fd11ee5a559f3e9b9564eaeb06f3))

## [2.11.0](https://github.com/cycle/database/compare/2.10.0...2.11.0) (2024-06-11)


### Features

* add INTERSECT and EXCEPT operators ([#204](https://github.com/cycle/database/issues/204)) ([b3cc5a3](https://github.com/cycle/database/commit/b3cc5a3b01c5771bfbe8950a8e8b1dba33f73da9))
* add the ability to specify the full name of the join type ([0fa0adf](https://github.com/cycle/database/commit/0fa0adf130def012273f1f2e69df3484100a8c62))


### Bug Fixes

* add parameter consideration when hashing the query part with orderBy ([c874f7d](https://github.com/cycle/database/commit/c874f7d4548a190f0415b3a2c94cc9a3955e9cfa))
* triggering changelog build ([4002820](https://github.com/cycle/database/commit/4002820fa99f88d96f27f533fda5b1d6e4836f67))


### Documentation

* add issue templates ([75086af](https://github.com/cycle/database/commit/75086af9abb974f15e7b942821413cdcbe627d55))
* added security.md file ([17aefde](https://github.com/cycle/database/commit/17aefde4d5e8b69c49abaa31a07fe77644a1e31b))
* move COC to .github directory ([08cadcb](https://github.com/cycle/database/commit/08cadcb4d6f88df71cb7260e3b9d1e950ce6294c))
* removing failing ci issue template ([7200063](https://github.com/cycle/database/commit/7200063e0bd57e0ff4be009652d01da3606eb62a))
* update CONTRIBUTING.md ([f6a9722](https://github.com/cycle/database/commit/f6a972200a8b1b8445b4583a9ac6239a8e74b630))
* updating contribution guide ([ad3fbf0](https://github.com/cycle/database/commit/ad3fbf0a02a27435a9f8749ce18c47c858e1d25e))


### Styles

* apply yamllint ([0506fb1](https://github.com/cycle/database/commit/0506fb100823042bc45d90b3507a781eb3225449))


### Dependencies

* **composer:** added ergebnis/composer-normalize ([1dddad4](https://github.com/cycle/database/commit/1dddad4181758e2b41151e1bdafe932b51249bc7))


### Tests

* add tests using fragments in orderBy ([239b061](https://github.com/cycle/database/commit/239b0616ad72d5b3a9785bba48a456484b1bde8d))
* fix tests ([56edfd4](https://github.com/cycle/database/commit/56edfd41dcd5c07d34504e1e835392bbe12e7006))


### Continuous Integration

* add cycle/gh-actions ([2507324](https://github.com/cycle/database/commit/2507324f819ad25eac86e7fb40df54c07276c91d))
* add default CODEOWNERS file ([c080e0c](https://github.com/cycle/database/commit/c080e0c7d7721533fb77ca9f710677ee1155fdf4))
* added coding-standards initial CI job ([9725b49](https://github.com/cycle/database/commit/9725b49edb0a4e87b0345e5fedae91cdfef5fa59))
* auto apply labels based on files and branches ([86971f0](https://github.com/cycle/database/commit/86971f0d08d49c5633958ca5b89158d588a228d6))
* do not include v prefix in tag ([d9587c3](https://github.com/cycle/database/commit/d9587c3f5f536c22e08f654b6332118995865eae))
* fixes in commit linting ([ca925bd](https://github.com/cycle/database/commit/ca925bdd36870f5dceff61eebfa3be62258fb2fe))
* push composer.lock to enable cache locks ([726a0fe](https://github.com/cycle/database/commit/726a0fea90dc6c97b3091e801eaf8d4f3967cf06))
* switch to github changelog type ([8275f0c](https://github.com/cycle/database/commit/8275f0c03d814a57ed2d4ce55f696b3612c74eca))
* use actions/labeler directly ([a9fa3b8](https://github.com/cycle/database/commit/a9fa3b808105b22513bc7a9d71e8fbd5865c3a16))
* use fixed versions for actions instead of master ([bd4d708](https://github.com/cycle/database/commit/bd4d7088ab410830dec0d34a3858af620e9672b2))

## [2.10.0](https://github.com/cycle/database/compare/2.9.0...v2.10.0) (2024-04-04)

### Features
- Add support JSON columns in `orderBy` statement by @msmakouz (#184)
- Add `mediumText` column type by @msmakouz (#178)
- Add support for the `NOT` operator in SQL queries. Add new methods `whereNot`, `andWhereNot`, and `orWhereNot` by @msmakouz (#185)

### Bug Fixes
- Fixed caching of SQL insert query with Fragment values by @msmakouz (#177)
- Fixed detection of enum values in PostgreSQL when a enum field has only one value by @msmakouz (#181)
- Fix psalm type for `DatabaseInterface::transaction()` method by @roxblnfk (#186)

### Continuous Integration
- Automate changelog and release management @lotyp (#189)

## [2.9.0](https://github.com/cycle/database/compare/2.8.1...2.9.0) (2024-03-27)

### Features
- Added `logInterpolatedQueries` for detailed query logging and refined `logQueryParameters` to accurately log query parameters as arrays, enhancing ORM debugging and monitoring. @lotyp (#165)
- Improved logging with enriched context in Driver.php, including driver details and query parameters. @lotyp (#165)
- Improved `orderBy` method to accept a null direction parameter, providing more flexibility when constructing queries by @butschster (#167)
- Added support for PostgreSQL-specific directions **NULLS FIRST** and **NULLS LAST** for more control over null value ordering in result sets by @butschster (#167)

## [2.8.1](https://github.com/cycle/database/compare/2.8.0...2.8.1) (2024-02-08)

### Bug Fixes
- Fix compiling of Fragment with parameters in the returning() definition by @msmakouz (#161)
- Fix comparison of Fragment in Column default value by @msmakouz (#162)

## [2.8.0](https://github.com/cycle/database/compare/2.7.1...2.8.0) (2024-02-08)

### Features
- The `withDatetimeMicroseconds` option now affects the interpolator by @msmakouz (#155)
- Postgres: support for multiple returning columns by @roxblnfk and @msmakouz (#157)
- MSSQL: support for multiple returning columns by @msmakouz (#160)

## [2.7.1](https://github.com/cycle/database/compare/2.7.0...2.7.1) (2023-12-22)

### Bug Fixes
- Fix setting `precision` and `scale` through attributes by @msmakouz (#148)
- Fix quoting with an underscore at the beginning by @msmakouz (#151)
- Fix behavior of the `Column::type()` method by adding default size to column type mappings by @msmakouz (#150)

## [2.7.0](https://github.com/cycle/database/compare/2.6.0...2.7.0) (2023-12-04)

### Features
- Add `varbinary` support in MySQL; optimize `size` attribute by @msmakouz (#146)
- Add the ability to use WHERE IN and WHERE NOT IN with array values. The value sequence may contain `FragmentInterface` objets by @msmakouz and @roxblnfk (#147)

## [2.6.0](https://github.com/cycle/database/compare/2.5.2...2.6.0) (2023-11-02)

### Features
- MySQL driver:
    - Add the type `smallInteger` by @gam6itko (#128)
    - Change mapping of the JSON type from `text` to `json` by @romanpravda (#121)
- Postgres driver:
    - Add the `restartIdentity` parameter to the `eraseTable` method by @msmakouz (#132)
    - Change mapping of the JSON type from `text` to `json` by @msmakouz (#134)
- All the drivers:
    - Add `enableForeignKeyConstraints` and `disableForeignKeyConstraints` methods in Driver Handlers by @msmakouz (#130)
    - Add an ability to disable the query cache before query using `withoutCache()` by @msmakouz and @roxblnfk (#137)
- JSON support:
    - Add methods to work with JSON columns by @msmakouz and @roxblnfk (#135)
    - Add an ability to set JSON default value as an array by @msmakouz and @roxblnfk (#138)

### Bug Fixes
- Fix incorrect parameters processing for JOIN subqueries by @smelesh (#133)

## [2.5.2](https://github.com/cycle/database/compare/2.5.1...2.5.2) (2023-07-03)

### Bug Fixes
- Fix Postgres schema restoring after reconnect by @msmakouz in (#126)

## [2.5.1](https://github.com/cycle/database/compare/2.5.0...2.5.1) (2023-06-09)

### Bug Fixes
- Fix drastic increase of insert statements on pg driver with complex primary keys by @wolfy-j (#122)

## [2.5.0](https://github.com/cycle/database/compare/2.4.1...2.5.0) (2023-05-12)

### Features
- Add ability to use non-primary serial column by @msmakouz (#106)
- Add ability to configure DB port passing a string by @msmakouz (#109)
- Add ability to define a custom type column by @msmakouz (#104)
- Add the ability to define `readonlySchema` for columns by @msmakouz (#116)
- Add `AbstractForeignKey::$index` property to enable/disable index creation by @msmakouz (#119)

### Bug Fixes
- Fix inserting an array of rowsets without calling the columns method by @msmakouz (#120)

### Miscellaneous
- Improve types for `TableInterface` and `ColumnInterface` by @vjik (#108)
- Fix typos by @arogachev (#110, #111)

## [2.4.1](https://github.com/cycle/database/compare/2.4.0...2.4.1) (2023-03-08)

### Bug Fixes
- Fix: add schema to Postgres dependency table names by @msmakouz (#102)
- Fix: don't add a table prefix when a column is quoting by @msmakouz (#103)

## [2.4.0](https://github.com/cycle/database/compare/2.3.0...2.4.0) (2023-02-01)

### ⚠ BREAKING CHANGES
* Since **v2.4.0**, interpolation in logs is **disabled** by default

### Features
- Add option `logQueryParameters` in the driver `options` to enable interpolation in SQL query logs by @msmakouz (#95)
- Add PostgreSQL specific data types by @msmakouz (#93)
- Add MySQL `SET` type support by @msmakouz (#92)

### Bug Fixes
- Fix Interpolator performance by @msmakouz (#94) thx @hustlahusky

## [2.3.0](https://github.com/cycle/database/compare/2.2.2...2.3.0) (2022-12-27)

### Features
- Add support for array values in the `IN` and `NOT IN` operators by @roxblnfk (#69, #70, #71)
- Add `PdoInterface` as a possible return type for the `Driver::getPDO()` method by @roxblnfk (#76)
- Add the `options` array parameter to all driver configs to pass additional driver options.
  The `withDatetimeMicroseconds` option can be set to `true` to store a `DateTime` with microseconds by @msmakouz (#86)
- Add a config recovery mechanism via `__set_state()` by @wakebit (#83)
- Add the ability to define the `size` of a `datetime` column by @msmakouz (#86)
- Add support for the unsigned and zerofill properties in MySQL Integer types by @roxblnfk (#88)

## [2.2.2](https://github.com/cycle/database/compare/2.2.1...2.2.2) (2022-09-27)

### Bug Fixes
- Fix transaction level changing on disconnect when transaction is starting by @roxblnfk (#76)

## [2.2.1](https://github.com/cycle/database/compare/2.2.0...2.2.1) (2022-07-02)

### Bug Fixes
- Hotfix: make the `$config` parameter of the `DatabaseConfig` constructor optional again by @roxblnfk

## [2.2.0](https://github.com/cycle/database/compare/2.1.3...2.2.0) (2022-06-23)

### Features
- Add supporting for PHP 8.1 Enumerations by @roxblnfk (#67)
- Add supporting for smallint column type by @gam6itko (#58)

### Bug Fixes
- Fix typo in the `\Cycle\Database\Schema\State::forgerForeignKey` method by @BeMySlaveDarlin (#53)
- Fix compatibility with Spiral Framework 3 packages:
    - Remove overriding of the `$config` property in the `DatabaseConfig` class by @roxblnfk (#64)
    - Update `composer.json` dependencies

## [2.1.3](https://github.com/cycle/database/compare/2.1.2...2.1.3) (2022-05-20)

### Bug Fixes
- Fix query interpolation by @roxblnfk (#60)

## [2.1.2](https://github.com/cycle/database/compare/2.1.1...2.1.2) (2022-01-20)

### Bug Fixes
- Fix wrong bind parameter order in a select query with join by @roxblnfk (#48)
- Fix access to unitialized driver property on `ActiveQuery::__debugInfo()` call by @roxblnfk (#46)

## [2.1.1](https://github.com/cycle/database/compare/2.1.0...2.1.1) (2022-01-12)

### Miscellaneous
- Fix phpdoc for the `SelectQuery::orderBy` method by @butschster (#45)
- Fix problem with driver cloning by @butschster (#44)

## [2.1.0](https://github.com/cycle/database/compare/2.0.0...2.1.0) (2021-12-31)

### Features
- Added `psr/log` dependency by @thgs (#35)
- Add ability to get/set driver name by @butschster (#38)
- Optimize fetching indexes and primary keys in Postgres driver by @hustlahusky (#37)

### Bug Fixes
- Fix type casting for column size value in the PG Column class by @rauanmayemir (#34)

### Miscellaneous
- Add `psr/log` dependency by @thgs (#35)
- Downgrade spiral dependencies to stable by @roxblnfk (#40)

## [2.0.0](https://github.com/cycle/database/compare/1.0.0...2.0.0) (2021-12-22)

### ⚠ BREAKING CHANGES
- Namespace `Spiral\Database` replaced with `Cycle\Database` @butschster (#1)
- Minimal PHP version is 8.0

### Features
- Added supporting for postgres schemas @butschster (#2)
- Added DTO configs @SerafimArts, @msmakouz (#10, #14)
- Added method to get transaction level @msmakouz (#23)
- Added `ColumnReturnableInterface` for database drivers @butschster (#25)

### Miscellaneous
- The package moved from `spiral/database` to `cycle/database`
- Improvements to `join` methods @butschster
- Tests have been restructured @butschster (#21)
