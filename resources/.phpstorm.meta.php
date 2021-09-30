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
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\ColumnInterface instead.
     */
    interface ColumnInterface extends \Cycle\Database\ColumnInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\DatabaseInterface instead.
     */
    interface DatabaseInterface extends \Cycle\Database\DatabaseInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\DatabaseProviderInterface instead.
     */
    interface DatabaseProviderInterface extends \Cycle\Database\DatabaseProviderInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\ForeignKeyInterface instead.
     */
    interface ForeignKeyInterface extends \Cycle\Database\ForeignKeyInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\IndexInterface instead.
     */
    interface IndexInterface extends \Cycle\Database\IndexInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\StatementInterface instead.
     */
    interface StatementInterface extends \Cycle\Database\StatementInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\TableInterface instead.
     */
    interface TableInterface extends \Cycle\Database\TableInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Database instead.
     */
    final class Database extends \Cycle\Database\Database
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\DatabaseManager instead.
     */
    final class DatabaseManager extends \Cycle\Database\DatabaseManager
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Table instead.
     */
    final class Table extends \Cycle\Database\Table
    {
    }
}

namespace Spiral\Database\Schema {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\ComparatorInterface instead.
     */
    interface ComparatorInterface extends \Cycle\Database\Schema\ComparatorInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\ElementInterface instead.
     */
    interface ElementInterface extends \Cycle\Database\Schema\ElementInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\AbstractColumn instead.
     */
    abstract class AbstractColumn extends \Cycle\Database\Schema\AbstractColumn
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\AbstractForeignKey instead.
     */
    abstract class AbstractForeignKey extends \Cycle\Database\Schema\AbstractForeignKey
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\AbstractIndex instead.
     */
    abstract class AbstractIndex extends \Cycle\Database\Schema\AbstractIndex
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\AbstractTable instead.
     */
    abstract class AbstractTable extends \Cycle\Database\Schema\AbstractTable
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\Comparator instead.
     */
    final class Comparator extends \Cycle\Database\Schema\Comparator
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\Reflector instead.
     */
    final class Reflector extends \Cycle\Database\Schema\Reflector
    {
    }
}

namespace Spiral\Database\Schema\Traits {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Schema\Traits\ElementTrait instead.
     */
    trait ElementTrait
    {
        use \Cycle\Database\Schema\Traits\ElementTrait;
    }
}

namespace Spiral\Database\Query {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\BuilderInterface instead.
     */
    interface BuilderInterface extends \Cycle\Database\Query\BuilderInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\QueryInterface instead.
     */
    interface QueryInterface extends \Cycle\Database\Query\QueryInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\ActiveQuery instead.
     */
    abstract class ActiveQuery extends \Cycle\Database\Query\ActiveQuery
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\DeleteQuery instead.
     */
    class DeleteQuery extends \Cycle\Database\Query\DeleteQuery
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\InsertQuery instead.
     */
    class InsertQuery extends \Cycle\Database\Query\InsertQuery
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\Interpolator instead.
     */
    final class Interpolator extends \Cycle\Database\Query\Interpolator
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\QueryBuilder instead.
     */
    final class QueryBuilder extends \Cycle\Database\Query\QueryBuilder
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\QueryParameters instead.
     */
    final class QueryParameters extends \Cycle\Database\Query\QueryParameters
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\SelectQuery instead.
     */
    class SelectQuery extends \Cycle\Database\Query\SelectQuery
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\UpdateQuery instead.
     */
    class UpdateQuery extends \Cycle\Database\Query\UpdateQuery
    {
    }
}

namespace Spiral\Database\Query\Traits {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\Traits\HavingTrait instead.
     */
    trait HavingTrait
    {
        use \Cycle\Database\Query\Traits\HavingTrait;
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\Traits\JoinTrait instead.
     */
    trait JoinTrait
    {
        use \Cycle\Database\Query\Traits\JoinTrait;
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\Traits\TokenTrait instead.
     */
    trait TokenTrait
    {
        use \Cycle\Database\Query\Traits\TokenTrait;
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Query\Traits\WhereTrait instead.
     */
    trait WhereTrait
    {
        use \Cycle\Database\Query\Traits\WhereTrait;
    }
}

namespace Spiral\Database\Injection {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Injection\FragmentInterface instead.
     */
    interface FragmentInterface extends \Cycle\Database\Injection\FragmentInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Injection\ParameterInterface instead.
     */
    interface ParameterInterface extends \Cycle\Database\Injection\ParameterInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Injection\ValueInterface instead.
     */
    interface ValueInterface extends \Cycle\Database\Injection\ValueInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Injection\Expression instead.
     */
    class Expression extends \Cycle\Database\Injection\Expression
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Injection\Fragment instead.
     */
    class Fragment extends \Cycle\Database\Injection\Fragment
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Injection\Parameter instead.
     */
    class Parameter extends \Cycle\Database\Injection\Parameter
    {
    }
}

namespace Spiral\Database\Exception {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\StatementExceptionInterface instead.
     */
    interface StatementExceptionInterface extends \Cycle\Database\Exception\StatementExceptionInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\BuilderException instead.
     */
    class BuilderException extends \Cycle\Database\Exception\BuilderException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\CompilerException instead.
     */
    class CompilerException extends \Cycle\Database\Exception\CompilerException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\ConfigException instead.
     */
    class ConfigException extends \Cycle\Database\Exception\ConfigException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\DatabaseException instead.
     */
    class DatabaseException extends \Cycle\Database\Exception\DatabaseException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\DBALException instead.
     */
    class DBALException extends \Cycle\Database\Exception\DBALException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\DefaultValueException instead.
     */
    class DefaultValueException extends \Cycle\Database\Exception\DefaultValueException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\DriverException instead.
     */
    class DriverException extends \Cycle\Database\Exception\DriverException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\HandlerException instead.
     */
    class HandlerException extends \Cycle\Database\Exception\HandlerException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\InterpolatorException instead.
     */
    class InterpolatorException extends \Cycle\Database\Exception\InterpolatorException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\SchemaException instead.
     */
    class SchemaException extends \Cycle\Database\Exception\SchemaException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\StatementExceptionInterface instead.
     */
    class StatementException extends \Cycle\Database\Exception\StatementException
    {
    }
}

namespace Spiral\Database\Exception\StatementException {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\StatementException\ConnectionException instead.
     */
    class ConnectionException extends ConnectionException
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Exception\StatementException\ConnectionException instead.
     */
    class ConstrainException extends ConstrainException
    {
    }
}

namespace Spiral\Database\Driver {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\CachingCompilerInterface instead.
     */
    interface CachingCompilerInterface extends \Cycle\Database\Driver\CachingCompilerInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\CompilerInterface instead.
     */
    interface CompilerInterface extends \Cycle\Database\Driver\CompilerInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\DriverInterface instead.
     */
    interface DriverInterface extends \Cycle\Database\Driver\DriverInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\HandlerInterface instead.
     */
    interface HandlerInterface extends \Cycle\Database\Driver\HandlerInterface
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Compiler instead.
     */
    abstract class Compiler extends \Cycle\Database\Driver\Compiler
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Driver instead.
     */
    abstract class Driver extends \Cycle\Database\Driver\Driver
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Handler instead.
     */
    abstract class Handler extends \Cycle\Database\Driver\Handler
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\CompilerCache instead.
     */
    final class CompilerCache extends \Cycle\Database\Driver\CompilerCache
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Quoter instead.
     */
    final class Quoter extends \Cycle\Database\Driver\Quoter
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\ReadonlyHandler instead.
     */
    final class ReadonlyHandler extends \Cycle\Database\Driver\ReadonlyHandler
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Statement instead.
     */
    final class Statement extends \Cycle\Database\Driver\Statement
    {
    }
}

namespace Spiral\Database\Driver\MySQL {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\MySQL\MySQLCompiler instead.
     */
    class MySQLCompiler extends \Cycle\Database\Driver\MySQL\MySQLCompiler
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\MySQL\MySQLDriver instead.
     */
    class MySQLDriver extends \Cycle\Database\Driver\MySQL\MySQLDriver
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\MySQL\MySQLHandler instead.
     */
    class MySQLHandler extends \Cycle\Database\Driver\MySQL\MySQLHandler
    {
    }
}

namespace Spiral\Database\Driver\MySQL\Exception {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\MySQL\Exception\MySQLException instead.
     */
    class MySQLException extends \Cycle\Database\Driver\MySQL\Exception\MySQLException
    {
    }
}

namespace Spiral\Database\Driver\MySQL\Schema {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\MySQL\Schema\MySQLColumn instead.
     */
    class MySQLColumn extends \Cycle\Database\Driver\MySQL\Schema\MySQLColumn
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\MySQL\Schema\MySQLForeignKey instead.
     */
    class MySQLForeignKey extends \Cycle\Database\Driver\MySQL\Schema\MySQLForeignKey
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\MySQL\Schema\MySQLIndex instead.
     */
    class MySQLIndex extends \Cycle\Database\Driver\MySQL\Schema\MySQLIndex
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\MySQL\Schema\MySQLTable instead.
     */
    class MySQLTable extends \Cycle\Database\Driver\MySQL\Schema\MySQLTable
    {
    }
}

namespace Spiral\Database\Driver\Postgres {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\PostgresCompiler instead.
     */
    class PostgresCompiler extends \Cycle\Database\Driver\Postgres\PostgresCompiler
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\PostgresDriver instead.
     */
    class PostgresDriver extends \Cycle\Database\Driver\Postgres\PostgresDriver
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\PostgresHandler instead.
     */
    class PostgresHandler extends \Cycle\Database\Driver\Postgres\PostgresHandler
    {
    }
}

namespace Spiral\Database\Driver\Postgres\Query {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery instead.
     */
    class PostgresInsertQuery extends \Cycle\Database\Driver\Postgres\Query\PostgresInsertQuery
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\Query\PostgresSelectQuery instead.
     */
    class PostgresSelectQuery extends \Cycle\Database\Driver\Postgres\Query\PostgresSelectQuery
    {
    }
}

namespace Spiral\Database\Driver\Postgres\Schema {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\Schema\PostgresColumn instead.
     */
    class PostgresColumn extends \Cycle\Database\Driver\Postgres\Schema\PostgresColumn
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\Schema\PostgresForeignKey instead.
     */
    class PostgresForeignKey extends \Cycle\Database\Driver\Postgres\Schema\PostgresForeignKey
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\Schema\PostgresIndex instead.
     */
    class PostgresIndex extends \Cycle\Database\Driver\Postgres\Schema\PostgresIndex
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\Postgres\Schema\PostgresTable instead.
     */
    class PostgresTable extends \Cycle\Database\Driver\Postgres\Schema\PostgresTable
    {
    }
}

namespace Spiral\Database\Driver\SQLite {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLite\SQLiteCompiler instead.
     */
    class SQLiteCompiler extends \Cycle\Database\Driver\SQLite\SQLiteCompiler
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLite\SQLiteDriver instead.
     */
    class SQLiteDriver extends \Cycle\Database\Driver\SQLite\SQLiteDriver
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLite\SQLiteHandler instead.
     */
    class SQLiteHandler extends \Cycle\Database\Driver\SQLite\SQLiteHandler
    {
    }
}

namespace Spiral\Database\Driver\SQLite\Schema {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLite\Schema\SQLiteColumn instead.
     */
    class SQLiteColumn extends \Cycle\Database\Driver\SQLite\Schema\SQLiteColumn
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLite\Schema\SQLiteForeignKey instead.
     */
    class SQLiteForeignKey extends \Cycle\Database\Driver\SQLite\Schema\SQLiteForeignKey
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLite\Schema\SQLiteIndex instead.
     */
    class SQLiteIndex extends \Cycle\Database\Driver\SQLite\Schema\SQLiteIndex
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLite\Schema\SQLiteTable instead.
     */
    class SQLiteTable extends \Cycle\Database\Driver\SQLite\Schema\SQLiteTable
    {
    }
}

namespace Spiral\Database\Driver\SQLServer {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLServer\SQLServerCompiler instead.
     */
    class SQLServerCompiler extends \Cycle\Database\Driver\SQLServer\SQLServerCompiler
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLServer\SQLServerDriver instead.
     */
    class SQLServerDriver extends \Cycle\Database\Driver\SQLServer\SQLServerDriver
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLServer\SQLServerHandler instead.
     */
    class SQLServerHandler extends \Cycle\Database\Driver\SQLServer\SQLServerHandler
    {
    }
}

namespace Spiral\Database\Driver\SQLServer\Schema {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn instead.
     */
    class SQLServerColumn extends \Cycle\Database\Driver\SQLServer\Schema\SQLServerColumn
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey instead.
     */
    class SQlServerForeignKey extends \Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLServer\Schema\SQLServerIndex instead.
     */
    class SQLServerIndex extends \Cycle\Database\Driver\SQLServer\Schema\SQLServerIndex
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Driver\SQLServer\Schema\SQLServerTable instead.
     */
    class SQLServerTable extends \Cycle\Database\Driver\SQLServer\Schema\SQLServerTable
    {
    }
}

namespace Spiral\Database\Config {

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Config\DatabaseConfig instead.
     */
    final class DatabaseConfig extends \Cycle\Database\Config\DatabaseConfig
    {
    }

    /**
     * @deprecated Since Cycle ORM 1.0, use Cycle\Database\Config\DatabasePartial instead.
     */
    final class DatabasePartial extends \Cycle\Database\Config\DatabasePartial
    {
    }
}
