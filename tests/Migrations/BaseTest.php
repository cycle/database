<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Migrations;

use Interop\Container\ContainerInterface;
use Spiral\Core\Container;
use Spiral\Core\NullMemory;
use Spiral\Database\Configs\DatabasesConfig;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Entities\Database;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Helpers\SynchronizationPool;
use Spiral\Database\Schemas\Prototypes\AbstractTable;
use Spiral\Database\Schemas\StateComparator;
use Spiral\Files\FileManager;
use Spiral\Migrations\Atomizer;
use Spiral\Migrations\Configs\MigrationsConfig;
use Spiral\Migrations\FileRepository;
use Spiral\Migrations\Migration;
use Spiral\Migrations\Migrator;
use Spiral\Reactor\ClassDeclaration;
use Spiral\Reactor\FileDeclaration;
use Spiral\Tokenizer\Configs\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    const PROFILING = ENABLE_PROFILING;

    /**
     * @var DatabaseManager
     */
    protected $dbal;

    /**
     * @var Migrator
     */
    protected $migrator;

    /**
     * @var Database
     */
    protected $db;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var FileRepository
     */
    protected $repository;

    public function setUp()
    {
        $this->container = $container = new Container();
        $this->dbal = $this->databaseManager($this->container);

        $this->migrator = new Migrator(
            $this->migrationsConfig(),
            $this->dbal,
            $this->repository = new FileRepository(
                $this->migrationsConfig(),
                $this->tokenizer(),
                new FileManager(),
                $this->container
            )
        );
    }

    public function tearDown()
    {
        $files = new FileManager();
        foreach ($files->getFiles(__DIR__ . '/fixtures/', '*.php') as $file) {
            $files->delete($file);
        }

        $this->db->getDriver()->setProfiling(false);

        $schemas = [];
        //Clean up
        foreach ($this->dbal->database()->getTables() as $table) {
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schemas[] = $schema;
        }

        //Clear all tables
        $syncBus = new SynchronizationPool($schemas);
        $syncBus->run();

        $this->db->getDriver()->setProfiling(true);
    }

    /**
     * @return MigrationsConfig
     */
    protected function migrationsConfig(): MigrationsConfig
    {
        return new MigrationsConfig([
            'directory' => __DIR__ . '/fixtures/',
            'database'  => 'default',
            'table'     => 'migrations',
            'safe'      => true
        ]);
    }

    protected function tokenizer(): Tokenizer
    {
        return new Tokenizer(
            new TokenizerConfig([
                'directories' => [
                    __DIR__ . '/fixtures/'
                ],
                'exclude'     => []
            ]),
            new FileManager(),
            new NullMemory()
        );
    }

    /**
     * @param ContainerInterface $container
     *
     * @return DatabaseManager
     */
    protected function databaseManager(ContainerInterface $container): DatabaseManager
    {
        $dbal = new DatabaseManager(
            $this->dbConfig = new DatabasesConfig([
                'default'     => 'default',
                'aliases'     => [],
                'databases'   => [],
                'connections' => []
            ]),
            $container
        );

        $dbal->addDatabase(
            $this->db = new Database($this->getDriver($container), 'default', 'tests_')
        );

        $dbal->addDatabase(new Database($this->getDriver($container), 'slave', 'slave_'));

        return $dbal;
    }

    /**
     * Database driver.
     *
     * @return Driver
     */
    abstract function getDriver(ContainerInterface $container = null): Driver;

    protected function assertSameAsInDB(AbstractTable $current)
    {
        $comparator = new StateComparator(
            $current->getState(),
            $this->schema($current->getName())->getState()
        );

        if ($comparator->hasChanges()) {
            $this->fail($this->makeMessage($current->getName(), $comparator));
        }
    }

    protected function makeMessage(string $table, StateComparator $comparator)
    {
        if ($comparator->isPrimaryChanged()) {
            return "Table '{$table}' not synced, primary indexes are different.";
        }

        if ($comparator->droppedColumns()) {
            return "Table '{$table}' not synced, columns are missing.";
        }

        if ($comparator->addedColumns()) {
            return "Table '{$table}' not synced, new columns found.";
        }

        if ($comparator->alteredColumns()) {

            $names = [];
            foreach ($comparator->alteredColumns() as $pair) {
                $names[] = $pair[0]->getName();
                print_r($pair);
            }

            return "Table '{$table}' not synced, column(s) '" . join("', '",
                    $names) . "' have been changed.";
        }

        if ($comparator->droppedForeigns()) {
            return "Table '{$table}' not synced, FKs are missing.";
        }

        if ($comparator->addedForeigns()) {
            return "Table '{$table}' not synced, new FKs found.";
        }


        return "Table '{$table}' not synced, no idea why, add more messages :P";
    }

    public function schema(string $table): AbstractTable
    {
        return $this->db->table($table)->getSchema();
    }

    protected function atomize(string $name, array $tables)
    {
        //Make sure name is unique
        $name = $name . '_' . crc32(microtime(true));

        $atomizer = new Atomizer(
            new Atomizer\MigrationRenderer(new Atomizer\AliasLookup($this->dbal))
        );

        foreach ($tables as $table) {
            $atomizer->addTable($table);
        }

        //Rendering
        $declaration = new ClassDeclaration($name, Migration::class);

        $declaration->method('up')->setPublic();
        $declaration->method('down')->setPublic();

        $atomizer->declareChanges($declaration->method('up')->getSource());
        $atomizer->revertChanges($declaration->method('down')->getSource());

        $file = new FileDeclaration();
        $file->addElement($declaration);

        $this->repository->registerMigration($name, $name, $file);
    }
}