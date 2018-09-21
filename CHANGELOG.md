CHANGELOG for 0.9.0 RC
======================

2.0.0 (21.09.2018)
-----
- massive refactor
- decoupling from Spiral\Component
- no more additional dependencies on ContainerInterface
- support for read/write database connections
- more flexible configuration
- less dependencies between classes
- interfaces have been exposed for table, column, index and foreignKeys
- new interface for driver, database, table, compiler and handler
- immutable quoter
- more tests
- custom exceptions for connection and constrain exceptions 

1.0.1 (15.06.2018)
-----
- MySQL driver can reconnect now

1.0.0 (02.03.2018)
-----
* Improved handling of renamed indexes associated with renamed columns

0.9.1 (07.02.2017)
-----
* Pagination split into separate package

0.9.0 (03.02.2017)
-----
* DBAL, Pagination and Migration component split from component repository
