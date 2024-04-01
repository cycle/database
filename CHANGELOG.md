# CHANGELOG

v2.10.0 (01.04.2024)
--------------------
- Add support **JSON** columns in **orderBy** statement by @msmakouz (#184)
- Add `mediumText` column type by @msmakouz (#178)
- Fix caching of SQL insert query with Fragment values by @msmakouz (#177)
- Fix detection of enum values in PostgreSQL when a enum field has only one value by @msmakouz (#181)

v2.9.0 (27.03.2024)
-------------------
- Added `logInterpolatedQueries` for detailed query logging and refined `logQueryParameters` to accurately log query parameters as arrays, enhancing ORM debugging and monitoring. @lotyp (#165)
- Improved logging with enriched context in Driver.php, including driver details and query parameters. @lotyp (#165)
- Improved `orderBy` method to accept a null direction parameter, providing more flexibility when constructing queries by @butschster (#167)
- Added support for PostgreSQL-specific directions **NULLS FIRST** and **NULLS LAST** for more control over null value ordering in result sets by @butschster (#167)

v2.8.1 (08.02.2024)
-------------------
- Fix compiling of Fragment with parameters in the returning() definition by @msmakouz (#161)
- Fix comparison of Fragment in Column default value by @msmakouz (#162)

v2.8.0 (08.02.2024)
-------------------
- The `withDatetimeMicroseconds` option now affects the interpolator by @msmakouz (#155)
- Postgres: support for multiple returning columns by @roxblnfk and @msmakouz (#157)
- MSSQL: support for multiple returning columns by @msmakouz (#160)

v2.7.1 (22.12.2023)
-------------------
- Fix setting `precision` and `scale` through attributes by @msmakouz (#148)
- Fix quoting with an underscore at the beginning by @msmakouz (#151)
- Fix behavior of the `Column::type()` method by adding default size to column type mappings by @msmakouz (#150)

v2.7.0 (04.12.2023)
-------------------
- Add `varbinary` support in MySQL; optimize `size` attribute by @msmakouz (#146)
- Add the ability to use WHERE IN and WHERE NOT IN with array values
  The value sequence may contain `FragmentInterface` objets by @msmakouz and @roxblnfk (#147)

v2.6.0 (02.11.2023)
-------------------
- Fix incorrect parameters processing for JOIN subqueries by @smelesh (#133)
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

v2.5.2 (03.07.2023)
-------------------
- Fix Postgres schema restoring after reconnect by @msmakouz in (#126)

v2.5.1 (09.06.2023)
-------------------
- Fix drastic increase of insert statements on pg driver with complex primary keys by @wolfy-j (#122)

v2.5.0 (12.05.2023)
-------------------
- Add ability to use non-primary serial column by @msmakouz (#106)
- Add ability to configure DB port passing a string by @msmakouz (#109)
- Add ability to define a custom type column by @msmakouz (#104)
- Add the ability to define `readonlySchema` for columns by @msmakouz (#116)
- Add `AbstractForeignKey::$index` property to enable/disable index creation by @msmakouz (#119)
- Fix inserting an array of rowsets without calling the columns method by @msmakouz (#120)
- Improve types for `TableInterface` and `ColumnInterface` by @vjik (#108)
- Fix typos by @arogachev (#110, #111)

v2.4.1 (08.03.2023)
-------------------
- Fix: add schema to Postgres dependency table names by @msmakouz (#102)
- Fix: don't add a table prefix when a column is quoting by @msmakouz (#103)

v2.4.0 (01.02.2023)
-------------------
- Add option `logQueryParameters` in the driver `options` to enable interpolation in SQL query logs. 
  Since **v2.4.0**, interpolation in logs is **disabled** by default by @msmakouz (#95)
- Add PostgreSQL specific data types by @msmakouz (#93)
- Add MySQL `SET` type support by @msmakouz (#92)
- Fix Interpolator performance by @msmakouz (#94) thx @hustlahusky

v2.3.0 (27.12.2022)
-------------------
- Add support for array values in the `IN` and `NOT IN` operators by @roxblnfk (#69, #70, #71)
- Add `PdoInterface` as a possible return type for the `Driver::getPDO()` method by @roxblnfk (#76)
- Add the `options` array parameter to all driver configs to pass additional driver options.
  The `withDatetimeMicroseconds` option can be set to `true` to store a `DateTime` with microseconds by @msmakouz (#86)
- Add a config recovery mechanism via `__set_state()` by @wakebit (#83)
- Add the ability to define the `size` of a `datetime` column by @msmakouz (#86)
- Add support for the unsigned and zerofill properties in MySQL Integer types by @roxblnfk (#88)

v2.2.2 (27.09.2022)
-------------------
- Fix transaction level changing on disconnect when transaction is starting by @roxblnfk (#76)

v2.2.1 (02.07.2022)
-------------------
- Hotfix: make the `$config` parameter of the `DatabaseConfig` constructor optional again by @roxblnfk

v2.2.0 (23.06.2022)
-------------------
- Add supporting for PHP 8.1 Enumerations by @roxblnfk (#67)
- Add supporting for smallint column type by @gam6itko (#58)
- Fix typo in the `\Cycle\Database\Schema\State::forgerForeignKey` method by @BeMySlaveDarlin (#53)
- Fix compatibility with Spiral Framework 3 packages:
  - Remove overriding of the `$config` property in the `DatabaseConfig` class by @roxblnfk (#64)
  - Update `composer.json` dependencies

v2.1.3 (20.05.2022)
-------------------
- Fix query interpolation by @roxblnfk (#60)

v2.1.2 (20.01.2022)
-------------------
- Fix wrong bind parameter order in a select query with join by @roxblnfk (#48)
- Fix access to unitialized driver property on `ActiveQuery::__debugInfo()` call by @roxblnfk (#46)

v2.1.1 (12.01.2022)
-------------------
- Fix phpdoc for the `SelectQuery::orderBy` method by @butschster (#45)
- Fix problem with driver cloning by @butschster (#44)

v2.1.0 (31.12.2021)
-------------------
- Add `psr/log` dependency by @thgs (#35)
- Add ability to get/set driver name by @butschster (#38)
- Optimize fetching indexes and primary keys in Postgres driver by @hustlahusky (#37)
- Fix type casting for column size value in the PG Column class by @rauanmayemir (#34)
- Downgrade spiral dependencies to stable by @roxblnfk (#40)

v2.0.0 (22.12.2021)
-------------------
- The package moved from `spiral/database` to `cycle/database`
- Namespace `Spiral\Database` replaced with `Cycle\Database` @butschster (#1)
- Minimal PHP version is 8.0 (#26, #31)
- Added supporting for postgres schemas @butschster (#2)
- Added DTO configs @SerafimArts, @msmakouz  (#10, #14)
- Improvements to `join` methods @butschster
- Tests have been restructured @butschster (#21)
- Added method to get transaction level @msmakouz (#23)
- Added `ColumnReturnableInterface` for database drivers @butschster (#25)
