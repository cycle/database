<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Migrations;

use Spiral\Core\Component;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Entities\Table;
use Spiral\Migrations\Configs\MigrationsConfig;
use Spiral\Migrations\Exceptions\MigrationException;
use Spiral\Migrations\Migration\State;

/**
 * MigrationManager component.
 */
class Migrator extends Component implements SingletonInterface
{
    /**
     * @var MigrationsConfig
     */
    private $config = null;

    /**
     * @invisible
     * @var DatabaseManager
     */
    protected $dbal = null;

    /**
     * @invisible
     * @var RepositoryInterface
     */
    protected $repository = null;

    /**
     * @param MigrationsConfig    $config
     * @param DatabaseManager     $dbal
     * @param RepositoryInterface $repository
     */
    public function __construct(
        MigrationsConfig $config,
        DatabaseManager $dbal,
        RepositoryInterface $repository
    ) {
        $this->config = $config;
        $this->dbal = $dbal;
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool
    {
        return $this->stateTable()->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        if ($this->isConfigured()) {
            return;
        }

        //Migrations table is pretty simple.
        $schema = $this->stateTable()->getSchema();

        /*
         * Schema update will automatically sync all needed data
         */
        $schema->primary('id');
        $schema->string('migration', 255)->nullable(false);
        $schema->datetime('time_executed')->datetime();
        $schema->index(['migration']);

        $schema->save();
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Get every available migration with valid meta information.
     *
     * @return MigrationInterface[]
     */
    public function getMigrations(): array
    {
        $result = [];
        foreach ($this->repository->getMigrations() as $migration) {
            //Populating migration status and execution time (if any)
            $result[] = $migration->withState($this->resolveStatus($migration->getState()));
        }

        return $result;
    }

    /**
     * Execute one migration and return it's instance.
     *
     * @param CapsuleInterface $capsule Default capsule to be used if none given.
     *
     * @return MigrationInterface|null
     */
    public function run(CapsuleInterface $capsule = null)
    {
        $capsule = $capsule ?? new MigrationCapsule($this->dbal);

        if (!$this->isConfigured()) {
            throw new MigrationException("Unable to run migration, Migrator not configured");
        }

        /**
         * @var MigrationInterface $migration
         */
        foreach ($this->getMigrations() as $migration) {
            if ($migration->getState()->getStatus() != State::STATUS_PENDING) {
                continue;
            }

            //Isolate migration commands in a capsule
            $migration = $migration->withCapsule($capsule);

            //Executing migration inside global transaction
            $this->execute(function () use ($migration) {
                $migration->up();
            });

            //Registering record in database
            $this->stateTable()->insertOne([
                'migration'     => $migration->getState()->getName(),
                'time_executed' => new \DateTime('now')
            ]);

            //Update migration state
            return $migration->withState(
                $this->resolveStatus($migration->getState())
            );
        }

        return null;
    }

    /**
     * Rollback last migration and return it's instance.
     *
     * @param CapsuleInterface $capsule Default capsule to be used if none given.
     *
     * @return MigrationInterface|null
     */
    public function rollback(CapsuleInterface $capsule = null)
    {
        $capsule = $capsule ?? new MigrationCapsule($this->dbal);

        if (!$this->isConfigured()) {
            throw new MigrationException("Unable to run migration, Migrator not configured");
        }

        /**
         * @var MigrationInterface $migration
         */
        foreach (array_reverse($this->getMigrations()) as $migration) {
            if ($migration->getState()->getStatus() != State::STATUS_EXECUTED) {
                continue;
            }

            //Isolate migration commands in a capsule
            $migration = $migration->withCapsule($capsule);

            //Executing migration inside global transaction
            $this->execute(function () use ($migration) {
                $migration->down();
            });

            //Flushing DB record
            $this->stateTable()->delete([
                'migration' => $migration->getState()->getName()
            ])->run();

            //Update migration state
            return $migration->withState(
                $this->resolveStatus($migration->getState())
            );
        }

        return null;
    }

    /**
     * Migration table, all migration information will be stored in it.
     *
     * @return Table
     */
    protected function stateTable(): Table
    {
        return $this->dbal->database(
            $this->config->getDatabase()
        )->table(
            $this->config->getTable()
        );
    }

    /**
     * Clarify migration state with valid status and execution time
     *
     * @param State $initialState
     *
     * @return State
     */
    protected function resolveStatus(State $initialState)
    {
        //Fetch migration information from database
        $state = $this->stateTable()
            ->select('id', 'time_executed')
            ->where(['migration' => $initialState->getName()])
            ->run()
            ->fetch();

        if (empty($state['time_executed'])) {
            return $initialState->withStatus(State::STATUS_PENDING);
        }

        return $initialState->withStatus(
            State::STATUS_EXECUTED,
            new \DateTime(
                $state['time_executed'],
                $this->stateTable()->getDatabase()->getDriver()->getTimezone()
            )
        );
    }

    /**
     * Run given code under transaction open for every driver.
     *
     * @param \Closure $closure
     *
     * @throws \Throwable
     */
    protected function execute(\Closure $closure)
    {
        $this->beginTransactions();
        try {
            call_user_func($closure);
        } catch (\Throwable $e) {
            $this->rollbackTransactions();
            throw $e;
        }

        $this->commitTransactions();
    }

    /**
     * Begin transaction for every available driver (we don't know what database migration related
     * to).
     */
    protected function beginTransactions()
    {
        foreach ($this->getDrivers() as $driver) {
            $driver->beginTransaction();
        }
    }

    /**
     * Rollback transaction for every available driver.
     */
    protected function rollbackTransactions()
    {
        foreach ($this->getDrivers() as $driver) {
            $driver->rollbackTransaction();
        }
    }

    /**
     * Commit transaction for every available driver.
     */
    protected function commitTransactions()
    {
        foreach ($this->getDrivers() as $driver) {
            $driver->commitTransaction();
        }
    }

    /**
     * Get all available drivers.
     *
     * @return Driver[]
     */
    protected function getDrivers(): array
    {
        $drivers = [];
        foreach ($this->dbal->getDatabases() as $database) {
            $driver = $database->getDriver();
            if (!isset($drivers["{$driver->getName()}.{$driver->getSource()}"])) {
                $drivers["{$driver->getName()}.{$driver->getSource()}"] = $database->getDriver();
            }
        }

        return $drivers;
    }
}