<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Database {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\ColumnInterface instead.
     */
    interface ColumnInterface extends \Cycle\Database\ColumnInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\DatabaseInterface instead.
     */
    interface DatabaseInterface extends \Cycle\Database\DatabaseInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\DatabaseProviderInterface instead.
     */
    interface DatabaseProviderInterface extends \Cycle\Database\DatabaseProviderInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\ForeignKeyInterface instead.
     */
    interface ForeignKeyInterface extends \Cycle\Database\ForeignKeyInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\IndexInterface instead.
     */
    interface IndexInterface extends \Cycle\Database\IndexInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\StatementInterface instead.
     */
    interface StatementInterface extends \Cycle\Database\StatementInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\TableInterface instead.
     */
    interface TableInterface extends \Cycle\Database\TableInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Database instead.
     */
    final class Database extends \Cycle\Database\Database
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\DatabaseManager instead.
     */
    final class DatabaseManager extends \Cycle\Database\DatabaseManager
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Table instead.
     */
    final class Table extends \Cycle\Database\Table
    {
    }
}

namespace Spiral\Database\Schema {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\ComparatorInterface instead.
     */
    interface ComparatorInterface extends \Cycle\Database\Schema\ComparatorInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\ElementInterface instead.
     */
    interface ElementInterface extends \Cycle\Database\Schema\ElementInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\AbstractColumn instead.
     */
    abstract class AbstractColumn extends \Cycle\Database\Schema\AbstractColumn
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\AbstractForeignKey instead.
     */
    abstract class AbstractForeignKey extends \Cycle\Database\Schema\AbstractForeignKey
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\AbstractIndex instead.
     */
    abstract class AbstractIndex extends \Cycle\Database\Schema\AbstractIndex
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\AbstractTable instead.
     */
    abstract class AbstractTable extends \Cycle\Database\Schema\AbstractTable
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\Comparator instead.
     */
    final class Comparator extends \Cycle\Database\Schema\Comparator
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\Reflector instead.
     */
    final class Reflector extends \Cycle\Database\Schema\Reflector
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\State instead.
     */
    final class State extends \Cycle\Database\Schema\State
    {
    }
}

namespace Spiral\Database\Schema\Traits {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Schema\Traits\ElementTrait instead.
     */
    trait ElementTrait
    {
        use \Cycle\Database\Schema\Traits\ElementTrait;
    }
}

namespace Spiral\Database\Query {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\BuilderInterface instead.
     */
    interface BuilderInterface extends \Cycle\Database\Query\BuilderInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\QueryInterface instead.
     */
    interface QueryInterface extends \Cycle\Database\Query\QueryInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\ActiveQuery instead.
     */
    abstract class ActiveQuery extends \Cycle\Database\Query\ActiveQuery
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\DeleteQuery instead.
     */
    class DeleteQuery extends \Cycle\Database\Query\DeleteQuery
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\InsertQuery instead.
     */
    class InsertQuery extends \Cycle\Database\Query\InsertQuery
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\Interpolator instead.
     */
    final class Interpolator extends \Cycle\Database\Query\Interpolator
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\QueryBuilder instead.
     */
    final class QueryBuilder extends \Cycle\Database\Query\QueryBuilder
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\QueryParameters instead.
     */
    final class QueryParameters extends \Cycle\Database\Query\QueryParameters
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\SelectQuery instead.
     */
    class SelectQuery extends \Cycle\Database\Query\SelectQuery
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\UpdateQuery instead.
     */
    class UpdateQuery extends \Cycle\Database\Query\UpdateQuery
    {
    }
}

namespace Spiral\Database\Query\Traits {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\Traits\HavingTrait instead.
     */
    trait HavingTrait
    {
        use \Cycle\Database\Query\Traits\HavingTrait;
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\Traits\JoinTrait instead.
     */
    trait JoinTrait
    {
        use \Cycle\Database\Query\Traits\JoinTrait;
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\Traits\TokenTrait instead.
     */
    trait TokenTrait
    {
        use \Cycle\Database\Query\Traits\TokenTrait;
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Query\Traits\WhereTrait instead.
     */
    trait WhereTrait
    {
        use \Cycle\Database\Query\Traits\WhereTrait;
    }
}

namespace Spiral\Database\Injection {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Injection\FragmentInterface instead.
     */
    interface FragmentInterface extends \Cycle\Database\Injection\FragmentInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Injection\ParameterInterface instead.
     */
    interface ParameterInterface extends \Cycle\Database\Injection\ParameterInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Injection\ValueInterface instead.
     */
    interface ValueInterface extends \Cycle\Database\Injection\ValueInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Injection\Expression instead.
     */
    class Expression extends \Cycle\Database\Injection\Expression
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Injection\Fragment instead.
     */
    class Fragment extends \Cycle\Database\Injection\Fragment
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Injection\Parameter instead.
     */
    class Parameter extends \Cycle\Database\Injection\Parameter
    {
    }
}

namespace Spiral\Database\Exception {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\StatementExceptionInterface instead.
     */
    interface StatementExceptionInterface extends \Cycle\Database\Exception\StatementExceptionInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\BuilderException instead.
     */
    class BuilderException extends \Cycle\Database\Exception\BuilderException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\CompilerException instead.
     */
    class CompilerException extends \Cycle\Database\Exception\CompilerException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\ConfigException instead.
     */
    class ConfigException extends \Cycle\Database\Exception\ConfigException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\DatabaseException instead.
     */
    class DatabaseException extends \Cycle\Database\Exception\DatabaseException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\DBALException instead.
     */
    class DBALException extends \Cycle\Database\Exception\DBALException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\DefaultValueException instead.
     */
    class DefaultValueException extends \Cycle\Database\Exception\DefaultValueException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\DriverException instead.
     */
    class DriverException extends \Cycle\Database\Exception\DriverException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\HandlerException instead.
     */
    class HandlerException extends \Cycle\Database\Exception\HandlerException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\InterpolatorException instead.
     */
    class InterpolatorException extends \Cycle\Database\Exception\InterpolatorException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\SchemaException instead.
     */
    class SchemaException extends \Cycle\Database\Exception\SchemaException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\StatementExceptionInterface instead.
     */
    class StatementException extends \Cycle\Database\Exception\StatementException
    {
    }
}

namespace Spiral\Database\Exception\StatementException {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\StatementException\ConnectionException instead.
     */
    class ConnectionException extends ConnectionException
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Exception\StatementException\ConnectionException instead.
     */
    class ConstrainException extends ConstrainException
    {
    }
}

namespace Spiral\Database\Driver {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\CachingCompilerInterface instead.
     */
    interface CachingCompilerInterface extends \Cycle\Database\Driver\CachingCompilerInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\CompilerInterface instead.
     */
    interface CompilerInterface extends \Cycle\Database\Driver\CompilerInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\DriverInterface instead.
     */
    interface DriverInterface extends \Cycle\Database\Driver\DriverInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\HandlerInterface instead.
     */
    interface HandlerInterface extends \Cycle\Database\Driver\HandlerInterface
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Compiler instead.
     */
    abstract class Compiler extends \Cycle\Database\Driver\Compiler
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Driver instead.
     */
    abstract class Driver extends \Cycle\Database\Driver\Driver
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Handler instead.
     */
    abstract class Handler extends \Cycle\Database\Driver\Handler
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\CompilerCache instead.
     */
    final class CompilerCache extends \Cycle\Database\Driver\CompilerCache
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Quoter instead.
     */
    final class Quoter extends \Cycle\Database\Driver\Quoter
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\ReadonlyHandler instead.
     */
    final class ReadonlyHandler extends \Cycle\Database\Driver\ReadonlyHandler
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Statement instead.
     */
    final class Statement extends \Cycle\Database\Driver\Statement
    {
    }
}

namespace Spiral\Database\Driver\MySQL {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\MySQL\MySQLCompiler instead.
     */
    class MySQLCompiler extends \Cycle\Database\Driver\MySQL\MySQLCompiler
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\MySQL\MySQLDriver instead.
     */
    class MySQLDriver extends \Cycle\Database\Driver\MySQL\MySQLDriver
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\MySQL\MySQLHandler instead.
     */
    class MySQLHandler extends \Cycle\Database\Driver\MySQL\MySQLHandler
    {
    }
}

namespace Spiral\Database\Driver\MySQL\Exception {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\MySQL\Exception\MySQLException instead.
     */
    class MySQLException extends \Cycle\Database\Driver\MySQL\Exception\MySQLException
    {
    }
}

namespace Spiral\Database\Driver\MySQL\Schema {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\MySQL\Schema\MySQLColumn instead.
     */
    class MySQLColumn extends \Cycle\Database\Driver\MySQL\Schema\MySQLColumn
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\MySQL\Schema\MySQLForeignKey instead.
     */
    class MySQLForeignKey extends \Cycle\Database\Driver\MySQL\Schema\MySQLForeignKey
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\MySQL\Schema\MySQLIndex instead.
     */
    class MySQLIndex extends \Cycle\Database\Driver\MySQL\Schema\MySQLIndex
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\MySQL\Schema\MySQLTable instead.
     */
    class MySQLTable extends \Cycle\Database\Driver\MySQL\Schema\MySQLTable
    {
    }
}

namespace Spiral\Database\Driver\Postgres {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\PostgresCompiler instead.
     */
    class PostgresCompiler extends \Cycle\Database\Driver\Postgres\PostgresCompiler
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\PostgresDriver instead.
     */
    class PostgresDriver extends \Cycle\Database\Driver\Postgres\PostgresDriver
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\PostgresHandler instead.
     */
    class PostgresHandler extends \Cycle\Database\Driver\Postgres\PostgresHandler
    {
    }
}

namespace Spiral\Database\Driver\Postgres\Query {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery instead.
     */
    class PostgresInsertQuery extends \Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\Query\PostgresSelectQuery instead.
     */
    class PostgresSelectQuery extends \Cycle\Database\Driver\Postgres\Query\PostgresSelectQuery
    {
    }
}

namespace Spiral\Database\Driver\Postgres\Schema {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\Schema\PostgresColumn instead.
     */
    class PostgresColumn extends \Cycle\Database\Driver\Postgres\Schema\PostgresColumn
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\Schema\PostgresForeignKey instead.
     */
    class PostgresForeignKey extends \Cycle\Database\Driver\Postgres\Schema\PostgresForeignKey
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\Schema\PostgresIndex instead.
     */
    class PostgresIndex extends \Cycle\Database\Driver\Postgres\Schema\PostgresIndex
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\Postgres\Schema\PostgresTable instead.
     */
    class PostgresTable extends \Cycle\Database\Driver\Postgres\Schema\PostgresTable
    {
    }
}

namespace Spiral\Database\Driver\SQLite {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLite\SQLiteCompiler instead.
     */
    class SQLiteCompiler extends \Cycle\Database\Driver\SQLite\SQLiteCompiler
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLite\SQLiteDriver instead.
     */
    class SQLiteDriver extends \Cycle\Database\Driver\SQLite\SQLiteDriver
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLite\SQLiteHandler instead.
     */
    class SQLiteHandler extends \Cycle\Database\Driver\SQLite\SQLiteHandler
    {
    }
}

namespace Spiral\Database\Driver\SQLite\Schema {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLite\Schema\SQLiteColumn instead.
     */
    class SQLiteColumn extends \Cycle\Database\Driver\SQLite\Schema\SQLiteColumn
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLite\Schema\SQLiteForeignKey instead.
     */
    class SQLiteForeignKey extends \Cycle\Database\Driver\SQLite\Schema\SQLiteForeignKey
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLite\Schema\SQLiteIndex instead.
     */
    class SQLiteIndex extends \Cycle\Database\Driver\SQLite\Schema\SQLiteIndex
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLite\Schema\SQLiteTable instead.
     */
    class SQLiteTable extends \Cycle\Database\Driver\SQLite\Schema\SQLiteTable
    {
    }
}

namespace Spiral\Database\Driver\SQLServer {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLServer\SQLServerCompiler instead.
     */
    class SQLServerCompiler extends \Cycle\Database\Driver\SQLServer\SQLServerCompiler
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLServer\SQLServerDriver instead.
     */
    class SQLServerDriver extends \Cycle\Database\Driver\SQLServer\SQLServerDriver
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLServer\SQLServerHandler instead.
     */
    class SQLServerHandler extends \Cycle\Database\Driver\SQLServer\SQLServerHandler
    {
    }
}

namespace Spiral\Database\Driver\SQLServer\Schema {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn instead.
     */
    class SQLServerColumn extends \Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey instead.
     */
    class SQlServerForeignKey extends \Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLServer\Schema\SQLServerIndex instead.
     */
    class SQLServerIndex extends \Cycle\Database\Driver\SQLServer\Schema\SQLServerIndex
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Driver\SQLServer\Schema\SQLServerTable instead.
     */
    class SQLServerTable extends \Cycle\Database\Driver\SQLServer\Schema\SQLServerTable
    {
    }
}

namespace Spiral\Database\Config {

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Config\DatabaseConfig instead.
     */
    final class DatabaseConfig extends \Cycle\Database\Config\DatabaseConfig
    {
    }

    /**
     * @deprecated since cycle/database 1.0, use Cycle\Database\Config\DatabasePartial instead.
     */
    final class DatabasePartial extends \Cycle\Database\Config\DatabasePartial
    {
    }
}
