# CHANGELOG

v2.2.1 (in progress)
-------------------

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
