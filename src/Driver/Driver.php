<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Driver;

use PDO;
use Psr\Log\LoggerAwareInterface;
use Spiral\Database\Driver\Traits\BuilderTrait;
use Spiral\Database\Driver\Traits\PDOTrait;
use Spiral\Database\Driver\Traits\ProfilingTrait;
use Spiral\Database\Exception\DriverException;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\Statement;

/**
 * Driver abstraction is responsible for DBMS specific set of functions and used by Databases to
 * hide implementation specific functionality. Extends PDODriver and adds ability to create driver
 * specific query builders and schemas (basically operates like a factory).
 */
abstract class Driver implements DriverInterface, LoggerAwareInterface
{
    use ProfilingTrait, PDOTrait, BuilderTrait;

    // One of DatabaseInterface types, must be set on implementation.
    protected const TYPE = "@undefined";

    // Driver specific class names.
    protected const TABLE_SCHEMA_CLASS = '';
    protected const COMMANDER          = '';
    protected const QUERY_COMPILER     = '';

    // DateTime format to be used to perform automatic conversion of DateTime objects.
    protected const DATETIME = 'Y-m-d H:i:s';

    /**
     * Connection configuration described in DBAL config file. Any driver can be used as data source
     * for multiple databases as table prefix and quotation defined on Database instance level.
     *
     * @var array
     */
    protected $options = [
        'profiling'  => false,

        //All datetime objects will be converted relative to this timezone (must match with DB timezone!)
        'timezone'   => 'UTC',

        //DSN
        'connection' => '',
        'username'   => '',
        'password'   => '',
        'options'    => [],
    ];

    /**
     * PDO connection options set.
     *
     * @var array
     */
    protected $pdoOptions = [
        PDO::ATTR_CASE             => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    /**
     * Transaction level (count of nested transactions). Not all drives can support nested
     * transactions.
     *
     * @var int
     */
    private $transactionLevel = 0;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options + $this->options;

        if (!empty($options['options'])) {
            //PDO connection options has to be stored under key "options" of config
            $this->pdoOptions = $options['options'] + $this->pdoOptions;
        }

        if (!empty($this->options['profiling'])) {
            $this->setProfiling(true);
        }
    }

    /**
     * Get driver source database or file name.
     *
     * @return string
     *
     * @throws DriverException
     */
    public function getSource(): string
    {
        if (preg_match('/(?:dbname|database)=([^;]+)/i', $this->options['connection'], $matches)) {
            return $matches[1];
        }

        throw new DriverException('Unable to locate source name');
    }

    /**
     * Database type driver linked to.
     *
     * @return string
     */
    public function getType(): string
    {
        return static::TYPE;
    }

    /**
     * Connection specific timezone, at this moment locked to UTC.
     *
     * @return \DateTimeZone
     */
    public function getTimezone(): \DateTimeZone
    {
        return new \DateTimeZone($this->options['timezone']);
    }

    /**
     * @inheritdoc
     */
    public function quote($value, int $type = PDO::PARAM_STR): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $this->formatDatetime($value);
        }

        return $this->getPDO()->quote($value, $type);
    }

    /**
     * @inheritdoc
     */
    public function identifier(string $identifier): string
    {
        return $identifier == '*' ? '*' : '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * @inheritdoc
     */
    public function getSchema(string $table, string $prefix = ''): AbstractTable
    {
        $schema = static::TABLE_SCHEMA_CLASS;

        return new $schema($this, $table, $prefix);
    }

    /**
     * @inheritdoc
     */
    public function getCompiler(string $prefix = ''): CompilerInterface
    {
        $compiler = static::QUERY_COMPILER;

        return new $compiler(new Quoter($this, $prefix));
    }

    /**
     * Force driver connection.
     *
     * @throws DriverException
     */
    public function connect()
    {
        if (!$this->isConnected()) {
            $this->pdo = $this->createPDO();
            $this->pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [Statement::class]);
        }
    }

    /**
     * Check if driver already connected.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return !empty($this->pdo);
    }

    /**
     * Disconnect driver.
     */
    public function disconnect()
    {
        $this->pdo = null;
        $this->transactionLevel = 0;
    }

    /**
     * Start SQL transaction with specified isolation level (not all DBMS support it). Nested
     * transactions are processed using savepoints.
     *
     * @link http://en.wikipedia.org/wiki/Database_transaction
     * @link http://en.wikipedia.org/wiki/Isolation_(database_systems)
     *
     * @param string $isolationLevel
     * @return bool
     */
    public function beginTransaction(string $isolationLevel = null): bool
    {
        ++$this->transactionLevel;

        if ($this->transactionLevel == 1) {
            if (!empty($isolationLevel)) {
                $this->isolationLevel($isolationLevel);
            }

            $this->isProfiling() && $this->getLogger()->info('Begin transaction');

            return $this->getPDO()->beginTransaction();
        }

        $this->savepointCreate($this->transactionLevel);

        return true;
    }

    /**
     * Commit the active database transaction.
     *
     * @return bool
     */
    public function commitTransaction(): bool
    {
        --$this->transactionLevel;

        if ($this->transactionLevel == 0) {
            $this->isProfiling() && $this->getLogger()->info('Commit transaction');

            return $this->getPDO()->commit();
        }

        $this->savepointRelease($this->transactionLevel + 1);

        return true;
    }

    /**
     * Rollback the active database transaction.
     *
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        --$this->transactionLevel;

        if ($this->transactionLevel == 0) {
            $this->isProfiling() && $this->getLogger()->info('Rollback transaction');

            return $this->getPDO()->rollBack();
        }

        $this->savepointRollback($this->transactionLevel + 1);

        return true;
    }

    /**
     * Set transaction isolation level, this feature may not be supported by specific database
     * driver.
     *
     * @param string $level
     */
    protected function isolationLevel(string $level)
    {
        if (!empty($level)) {
            $this->isProfiling() && $this->getLogger()->info("Set transaction isolation level to '{$level}'");
            $this->execute("SET TRANSACTION ISOLATION LEVEL {$level}");
        }
    }

    /**
     * Create nested transaction save point.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointCreate(int $level)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: new savepoint 'SVP{$level}'");
        $this->execute('SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * Commit/release savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointRelease(int $level)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: release savepoint 'SVP{$level}'");
        $this->execute('RELEASE SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * Rollback savepoint.
     *
     * @link http://en.wikipedia.org/wiki/Savepoint
     *
     * @param int $level Savepoint name/id, must not contain spaces and be valid database identifier.
     */
    protected function savepointRollback(int $level)
    {
        $this->isProfiling() && $this->getLogger()->info("Transaction: rollback savepoint 'SVP{$level}'");
        $this->execute('ROLLBACK TO SAVEPOINT ' . $this->identifier("SVP{$level}"));
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'connection' => $this->options['connection'],
            'connected'  => $this->isConnected(),
            'profiling'  => $this->isProfiling(),
            'source'     => $this->getSource(),
            'options'    => $this->pdoOptions,
        ];
    }

    /**
     * Create instance of configured PDO class.
     *
     * @return PDO
     */
    protected function createPDO(): PDO
    {
        return new PDO(
            $this->options['connection'],
            $this->options['username'],
            $this->options['password'],
            $this->pdoOptions
        );
    }

    /**
     * Convert DateTime object into local database representation. Driver will automatically force
     * needed timezone.
     *
     * @param \DateTimeInterface $value
     * @return string
     *
     * @throws \Exception
     */
    protected function formatDatetime(\DateTimeInterface $value): string
    {
        //Immutable and prepared??
        $datetime = new \DateTime('now', $this->getTimezone());
        $datetime->setTimestamp($value->getTimestamp());

        return $datetime->format(static::DATETIME);
    }
}
