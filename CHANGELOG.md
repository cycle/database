# Changelog

## [2.24.1](https://github.com/cycle/database/compare/2.24.0...2.24.1) (2026-11-22)


### Documentation

* drop the public issue step from the security policy ([69ec5d7](https://github.com/cycle/database/commit/69ec5d7660de71a3ef40b0cbf86f174fa500e8d8))


### Styles

* **php-cs-fixer:** fix coding standards ([a51fea3](https://github.com/cycle/database/commit/a51fea3aaed49014e174c9c48634a71eed189ca3))


### Tests

* **Config:** cover the default Postgres schema and a driver without a name ([b794853](https://github.com/cycle/database/commit/b794853bdab987907241b64de4b90c714e1629c2))
* **Driver:** cover a failed reconnect on begin, begin without reconnect and dropping an enum column ([96ad79d](https://github.com/cycle/database/commit/96ad79ddc44b627a4f1d5b65915affaf80a2c7e8))
* **Driver:** cover failed transaction calls, enum quoting and the compiler's rejected fragments ([6d047b3](https://github.com/cycle/database/commit/6d047b36dee70e1b625df7fc5d39b4c3f2d8256a))
* **Injection:** cover given parameter instances and restoring a JSON expression ([151dcb4](https://github.com/cycle/database/commit/151dcb4d7f34fe64963b22cb90ec6b7178ab2385))
* **MySQL:** cover table engines, bit and zero timestamp defaults, charset changes and a dropped link ([b787061](https://github.com/cycle/database/commit/b7870611ec36e400e977f21e3bb67d255c13127c))
* **Postgres:** cover native enums read in bulk, primary types on existing columns, abandoned cursors and the $user schema ([cf3b35e](https://github.com/cycle/database/commit/cf3b35e810f6c4a8fad4d3c49518dd317b08e01f))
* **Query:** cover where argument checks, a full join and the state given to query constructors ([758233b](https://github.com/cycle/database/commit/758233b4193d068304161bbca52f8a3d441784b2))
* **Schema:** cover redeclared elements, unsupported index sorting and a driver outside the Driver hierarchy ([8711198](https://github.com/cycle/database/commit/871119834d7b6b863f16b544a3951a404910c075))
* **SQLServer:** cover enum, decimal and default changes of existing columns ([6e9d60d](https://github.com/cycle/database/commit/6e9d60d7f382834fca4d17855154c14357d29328))

## [2.24.0](https://github.com/cycle/database/compare/2.23.5...2.24.0) (2026-11-21)


### Features

* require PHP 8.2 and polish the tooling ([#287](https://github.com/cycle/database/issues/287)) ([5b8b7d9](https://github.com/cycle/database/commit/5b8b7d9fec0341a88b5d7b9eba44fc778325093b))


### Documentation

* add the mutation testing badge ([449aac8](https://github.com/cycle/database/commit/449aac8845100520890f623b60f15f2e22f0293f))
* move the Discord badge to the first row ([#289](https://github.com/cycle/database/issues/289)) ([0c46485](https://github.com/cycle/database/commit/0c4648542549a9c547c7aa7fdda3a30de5a65bf7))
* restructure the README with the Cycle header and current badges ([#288](https://github.com/cycle/database/issues/288)) ([c3b2470](https://github.com/cycle/database/commit/c3b2470301c0d46647e3ca8a36b510c714fb0698))


### Dependencies

* require cycle/testing ^0.1.2 ([#308](https://github.com/cycle/database/issues/308)) ([f3f1a36](https://github.com/cycle/database/commit/f3f1a36d9d5dc61991fbab52a59c8bd8e8755594))


### Code Refactoring

* convert dates with DateTimeImmutable::createFromInterface ([#309](https://github.com/cycle/database/issues/309)) ([cf393e0](https://github.com/cycle/database/commit/cf393e0253d300eca71aa4211b878fd32845f011))


### Tests

* **Config:** cover the DSN builder, hidden passwords and the database read from a DSN ([d165078](https://github.com/cycle/database/commit/d1650784855c2ae0062b535bd1d8a9dddc44c85e))
* **Config:** cover the precedence of connection and database keys ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* convert PHPUnit tests to Testo with Rector ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* **Driver:** cover reconnects, savepoints, query caching and error mapping edges ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* **Driver:** pin the exact SQL of the compiler and identifier quoting ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* drop the never-run Postgres CreateTableWithSchema tests ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* drop the PHPUnit harness ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* generate regression cases with cycle-testing new-case ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* kill escaped mutants ([#298](https://github.com/cycle/database/issues/298)) ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* move the test suites up to tests/ ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* **OnConflict:** cover conflict targets and cache keys of upserts ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* raise line coverage ([#301](https://github.com/cycle/database/issues/301)) ([d165078](https://github.com/cycle/database/commit/d1650784855c2ae0062b535bd1d8a9dddc44c85e))
* run the tests on Testo with cycle/testing ([#293](https://github.com/cycle/database/issues/293)) ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* run the tests on the cycle/testing harness ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* **Schema:** cover column options, state copies and comparator listings ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* **Schema:** cover rejected primary key changes, reflector rollback and index columns ([d165078](https://github.com/cycle/database/commit/d1650784855c2ae0062b535bd1d8a9dddc44c85e))
* **Schema:** cover table declarations, state handling and schema normalization ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* **Schema:** cover the readonly schema handler ([d165078](https://github.com/cycle/database/commit/d1650784855c2ae0062b535bd1d8a9dddc44c85e))
* **Schema:** cover the reflector run and table ordering ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* **SelectQuery:** cover array conditions, row locks and query copies of aggregates ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* split Testo suites into Feature and Regression ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* **SQLServer:** cover binary LOB parameters and a cursor whose declaration fails ([d165078](https://github.com/cycle/database/commit/d1650784855c2ae0062b535bd1d8a9dddc44c85e))
* wire cycle/testing and Testo ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))


### Continuous Integration

* align the security workflow with the reference ([#291](https://github.com/cycle/database/issues/291)) ([15af19a](https://github.com/cycle/database/commit/15af19a1d132f8772958a09869bb14374190f78f))
* pin the root version so composer resolves cycle/testing ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* pin the shared cycle/gh-actions workflows to v4.2.0 ([#303](https://github.com/cycle/database/issues/303)) ([fe58b51](https://github.com/cycle/database/commit/fe58b51d213b4a568ad33f9f06a0ed79333726fe))
* raise the mutation score gate ([6750c96](https://github.com/cycle/database/commit/6750c96c53ae55fed0002a3375f59b92cb17cec6))
* run mutation testing through the shared cycle/gh-actions workflow ([449aac8](https://github.com/cycle/database/commit/449aac8845100520890f623b60f15f2e22f0293f))
* run mutation testing with Infection ([#296](https://github.com/cycle/database/issues/296)) ([449aac8](https://github.com/cycle/database/commit/449aac8845100520890f623b60f15f2e22f0293f))
* run tests through the shared cycle/gh-actions workflow ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* run the tests on Testo with the cycle/testing databases ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))
* send only the mutation score to the Stryker dashboard ([#297](https://github.com/cycle/database/issues/297)) ([68115cb](https://github.com/cycle/database/commit/68115cb106524ec5227a11727cc31ba45176a50f))
* show the test job as Testo ([5165a04](https://github.com/cycle/database/commit/5165a04126f32e3d541515203e805f1ae1070c9d))

## [2.23.5](https://github.com/cycle/database/compare/2.23.4...2.23.5) (2026-11-19)


### Bug Fixes

* **JSON:** escape JSON path keys as JSON strings ([390e414](https://github.com/cycle/database/commit/390e414a5271d3478b0e8208fef4103a89b6b3b7))
* **JSON:** keep a plus sign before an apostrophe in JSON paths ([#284](https://github.com/cycle/database/issues/284)) ([cd788b2](https://github.com/cycle/database/commit/cd788b2d648f355b02eda0a464b0e261b4be4eb5))
* **MySQL:** write JSON paths with a backslash as hex literals ([5924b9d](https://github.com/cycle/database/commit/5924b9df07f5b74d78b9fb99f84b0a1dadb51438))

## [2.23.4](https://github.com/cycle/database/compare/2.23.3...2.23.4) (2026-11-19)


### Bug Fixes

* **DatabaseManager:** make setLogger() reach every driver ([#273](https://github.com/cycle/database/issues/273)) ([73a8306](https://github.com/cycle/database/commit/73a8306c0ed267daf239149ec23206c0ea26b7cc))
* **JSON:** keep every array index in JSON paths ([38a8f84](https://github.com/cycle/database/commit/38a8f84b0f152ca10c640dba4eadf7b42ed18275))
* **MySQL:** keep auto-increment columns out of the primary key ([#275](https://github.com/cycle/database/issues/275)) ([90219cf](https://github.com/cycle/database/commit/90219cf85c1c294757871797b73961633ddb7d77)), closes [#274](https://github.com/cycle/database/issues/274)
* **SQLServer:** compare array indexes as strings in JSON contains-key conditions ([402ce34](https://github.com/cycle/database/commit/402ce34c44c1f3c7ae6ffb70f30f9119597b764c))
* **SQLServer:** escape the key in JSON contains-key conditions ([9f39569](https://github.com/cycle/database/commit/9f39569670803816b095052689ca6f4791e53f56))
* **SQLServer:** keep every array index in JSON contains-key conditions ([94b3501](https://github.com/cycle/database/commit/94b35010def69279ddc3b4b36815724e90dc5d42))
* **SQLServer:** write JSON paths and keys as Unicode literals ([61f0d0e](https://github.com/cycle/database/commit/61f0d0e5188c7a8722f4948060231b9d28f9b752))


### Continuous Integration

* run cs-fix on the minimum supported PHP ([#280](https://github.com/cycle/database/issues/280)) ([6b6d5b4](https://github.com/cycle/database/commit/6b6d5b4304e77f5b61c1739fc46c9dd0010079fc))

## [2.23.3](https://github.com/cycle/database/compare/2.23.2...2.23.3) (2026-09-27)


### Bug Fixes

* **Postgres:** escape apostrophes in JSON path segments ([#271](https://github.com/cycle/database/issues/271)) ([da09e24](https://github.com/cycle/database/commit/da09e24964b3921f9c4952b3cdc915a98f8daba0))

## [2.23.2](https://github.com/cycle/database/compare/2.23.1...2.23.2) (2026-09-18)


### Bug Fixes

* **Driver:** classify exceptions by SQLSTATE before the message ([#270](https://github.com/cycle/database/issues/270)) ([8a83940](https://github.com/cycle/database/commit/8a8394048fe197bb08533c5efffca1f7c384c779)), closes [#268](https://github.com/cycle/database/issues/268)


### Documentation

* **DatabaseInterface:** annotate getSchemas() as a magic method ([ef2144e](https://github.com/cycle/database/commit/ef2144e13c5b242e455308c84881213a2152aeb8))

## [2.23.1](https://github.com/cycle/database/compare/2.23.0...2.23.1) (2026-08-19)


### Documentation

* update PHP version requirement to 8.1 in README ([c864c37](https://github.com/cycle/database/commit/c864c37a11ea6667562de1723c0c4c0680ca0083))

## [2.23.0](https://github.com/cycle/database/compare/2.22.1...2.23.0) (2026-08-19)


### Features

* add `Database::getSchemas()` ([#265](https://github.com/cycle/database/issues/265)) ([6a9cf21](https://github.com/cycle/database/commit/6a9cf21423640df2f7b8c6e653e93d07a9123f42))

## [2.22.1](https://github.com/cycle/database/compare/2.22.0...2.22.1) (2026-08-11)


### Performance Improvements

* **Schema:** batch table introspection queries ([#262](https://github.com/cycle/database/issues/262)) ([7e2d488](https://github.com/cycle/database/commit/7e2d488e9860ba2160bf30fd62167426b58f4f61))

## [2.22.0](https://github.com/cycle/database/compare/2.21.0...2.22.0) (2026-08-09)


### Features

* **SQLite:** support the RETURNING clause on INSERT/UPSERT ([#260](https://github.com/cycle/database/issues/260)) ([4d85c6f](https://github.com/cycle/database/commit/4d85c6ffae84cb2f9d39a2b719720d9a81b488bc))

## [2.21.0](https://github.com/cycle/database/compare/2.20.0...2.21.0) (2026-06-24)


### Features

* **OnConflict:** index-inference predicate for ON CONFLICT (Postgres/SQLite) ([#258](https://github.com/cycle/database/issues/258)) ([bdd2cea](https://github.com/cycle/database/commit/bdd2cea509efbb1c63d35d14c4addb55ea0cc005))


### Bug Fixes

* **MySQL:** omit row alias on upsert DO NOTHING — the alias made the no-op `col = col` ambiguous at runtime ([bdd2cea](https://github.com/cycle/database/commit/bdd2cea509efbb1c63d35d14c4addb55ea0cc005))
* **Postgres:** unquote EXCLUDED in upsert DO UPDATE — quoted "EXCLUDED" broke every update-branch upsert at runtime ([bdd2cea](https://github.com/cycle/database/commit/bdd2cea509efbb1c63d35d14c4addb55ea0cc005))

## [2.20.0](https://github.com/cycle/database/compare/2.19.0...2.20.0) (2026-06-11)


### Features

* **SelectQuery:** refine Psalm integer types on query bounds ([#257](https://github.com/cycle/database/issues/257)) ([2cae328](https://github.com/cycle/database/commit/2cae328e5025fa4c6a8d00a01d63c0da31b35c21))


### Bug Fixes

* **SelectQuery:** process the last partial chunk in `runChunks()` ([#255](https://github.com/cycle/database/issues/255)) ([38c3403](https://github.com/cycle/database/commit/38c340327f7e2ce45d9485df77e8dc6546ddd779))

## [2.19.0](https://github.com/cycle/database/compare/2.18.0...2.19.0) (2026-05-28)


### Features

* Add `wrapOnWhere()` method to group JOIN ON conditions ([#253](https://github.com/cycle/database/issues/253)) ([aafa2f6](https://github.com/cycle/database/commit/aafa2f62970a0c29a2964a32c48a05607943992e))


### Tests

* **OnConflict:** add tests for cache key generation with update parameters ([a064944](https://github.com/cycle/database/commit/a064944c741f4d29507ff9fa8db241386356a583))

## [2.18.0](https://github.com/cycle/database/compare/2.17.0...2.18.0) (2026-05-28)


### Features

* Add `InsertQuery::onConflict()` method ([#249](https://github.com/cycle/database/issues/249)) ([395a7ff](https://github.com/cycle/database/commit/395a7ff42116c1d4978d5cb20f4e66786c10b906))
* Add `SelectQuery::wrapWhere()` to group WHERE conditions ([#250](https://github.com/cycle/database/issues/250)) ([8f7c03a](https://github.com/cycle/database/commit/8f7c03a589260716d0211202e9969c4c3d0d285c))

## [2.17.0](https://github.com/cycle/database/compare/2.16.0...2.17.0) (2026-05-14)


### Features

* Add server-side cursors for streaming SELECT results ([#247](https://github.com/cycle/database/issues/247)) ([2acc10e](https://github.com/cycle/database/commit/2acc10e0e709c24f35fc3d0d93cf57189ead89fb))


### Code Refactoring

* Rename `CursorableInterface` to `CursorInterface` ([89dbfda](https://github.com/cycle/database/commit/89dbfdabf3361655316870ade76155a9fa545712))

## [2.16.0](https://github.com/cycle/database/compare/2.15.2...2.16.0) (2026-03-19)


### Features

* Add charset and collation support for MySQL columns ([#246](https://github.com/cycle/database/issues/246)) ([26e33ef](https://github.com/cycle/database/commit/26e33efc15e5e8746c1e5c5d2d338a6d06929fa3))
* Added $distinct parameter to SelectQuery::count ([#242](https://github.com/cycle/database/issues/242)) ([23842c0](https://github.com/cycle/database/commit/23842c068f76df42d76f5c19b56c2e3c4f7ac436))
* update PHP version requirement to 8.1 ([#244](https://github.com/cycle/database/issues/244)) ([16e0874](https://github.com/cycle/database/commit/16e0874b80b1aa665ae9919e1f0e87045ce18364))


### Styles

* **php-cs-fixer:** fix coding standards ([16e0874](https://github.com/cycle/database/commit/16e0874b80b1aa665ae9919e1f0e87045ce18364))

## [2.15.2](https://github.com/cycle/database/compare/2.15.1...2.15.2) (2026-02-16)


### Bug Fixes

* Replace deprecated MySQL constant with scalar ([#237](https://github.com/cycle/database/issues/237)) ([b1a9cd7](https://github.com/cycle/database/commit/b1a9cd777c13a15b59d8d2ca90e3095a1b2e4720))

## [2.15.1](https://github.com/cycle/database/compare/2.15.0...2.15.1) (2025-12-12)


### Bug Fixes

* correct condition check for named parameters in Interpolator ([#238](https://github.com/cycle/database/issues/238)) ([283709e](https://github.com/cycle/database/commit/283709e7f7d6cba4c2a144744be261d0abb9f7d0))

## [2.15.0](https://github.com/cycle/database/compare/2.14.0...2.15.0) (2025-07-22)


### Features

* add `snowflake` column type alias ([#235](https://github.com/cycle/database/issues/235)) ([49a90c7](https://github.com/cycle/database/commit/49a90c7062c90f44e5e072d90f14a5a472643e8a))

## [2.14.0](https://github.com/cycle/database/compare/2.13.0...2.14.0) (2025-07-14)


### Features

* add parameters to SubQuery ([74dbd7c](https://github.com/cycle/database/commit/74dbd7c11dc8812f54d4145c0fb3d6c07693d829))
* add SubQuery injection ([8079759](https://github.com/cycle/database/commit/80797599e1d3aff28c76e69904748df0e7d6407b))
* add ULID column type ([#233](https://github.com/cycle/database/issues/233)) ([f8b3a87](https://github.com/cycle/database/commit/f8b3a87222aa94a7ebd9e5776bc61897ad10237e))
* **MySQL:** add `first` and `after` into column declaration ([#226](https://github.com/cycle/database/issues/226)) ([7ad2df1](https://github.com/cycle/database/commit/7ad2df1ccf4498ac1a5067a0abc8ded55c6d06cd))


### Bug Fixes

* **MySQL:** unsigned boolean Columns comparison ([#234](https://github.com/cycle/database/issues/234)) ([82378ea](https://github.com/cycle/database/commit/82378ea94653eaf0b558149e86b7a6ebec94872e))


### Documentation

* cover SubQuery with comments ([8579419](https://github.com/cycle/database/commit/85794199f02e4d2f60f8f66de21f18e7968aafc5))


### Styles

* fix code style; rename `SubQueryInjection` to `SubQuery` ([b9cf08e](https://github.com/cycle/database/commit/b9cf08e1ca3911ee4cb3aab513f597988221b3eb))
* improve code formatting and consistency in Compiler, Jsoner, and SubQuery ([02484a6](https://github.com/cycle/database/commit/02484a66a23bac5f2fe3711944890642f0a4a7a2))


### Code Refactoring

* rename `selectSubQuery` to `subQuery` and update related query logic ([727ca0e](https://github.com/cycle/database/commit/727ca0edccd01cb0ba1133240712c65775cc6bd1))


### Tests

* enhance subquery tests with additional conditions and parameters ([09013c6](https://github.com/cycle/database/commit/09013c670cadcd865529b7c39578db2413001631))
* **MySQL:** Added a test case proving bug with boolean columns ([c2c05d1](https://github.com/cycle/database/commit/c2c05d191d5c4379fb9be58599012158ea3b9db6))
* **MySQL:** enhance boolean column configuration tests with zerofill and nullable checks ([0129e7f](https://github.com/cycle/database/commit/0129e7f0c49e2cefb7d986b0ad0d311bdcdf7738))

## [2.13.0](https://github.com/cycle/database/compare/2.12.0...2.13.0) (2025-03-27)


### Features

* add `getComment()` method annotation into `ColumnInterface` ([02c4962](https://github.com/cycle/database/commit/02c4962f8b2782a8273571ed50d50e1281f3f93c))
* Add getComment() method to `AbstractColumn` ([9e780d8](https://github.com/cycle/database/commit/9e780d8899d981778b4152757d39504029a54fcc))
* add public `Driver::clearCache()` method ([3cc2757](https://github.com/cycle/database/commit/3cc2757e70aea7a65afdd80495649e1422a144a6))


### Continuous Integration

* update workflow actions ([d55b36f](https://github.com/cycle/database/commit/d55b36fb538565f88efeb2abd280ead26e4ef025))

## [2.12.0](https://github.com/cycle/database/compare/2.11.3...2.12.0) (2025-02-17)


### Features

* Reconnect on MySQL disconnected by inactivity ([3f776ab](https://github.com/cycle/database/commit/3f776ab97f0b7d64c5fd9d1f65474c37e78e17b3))


### Tests

* update MSSQL config ([14ec20e](https://github.com/cycle/database/commit/14ec20eb807888476c91775fb87f5ac11b141555))


### Continuous Integration

* add ODBC driver to tests with codecov ([7dfd98e](https://github.com/cycle/database/commit/7dfd98e9cb166dc2dbd98907ad26e03845ee608f))

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
