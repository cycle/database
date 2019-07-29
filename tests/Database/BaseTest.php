<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Spiral\Database\Database;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Driver\Handler;
use Spiral\Database\Schema\AbstractColumn;
use Spiral\Database\Schema\AbstractForeignKey;
use Spiral\Database\Schema\AbstractIndex;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\Schema\Comparator;

abstract class BaseTest extends TestCase
{
    public static $config;
    public const DRIVER = null;

    protected static $driverCache = [];

    /** @var Driver */
    protected $driver;

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return Database|null When non empty null will be given, for safety, for science.
     */
    protected function db(string $name = 'default', string $prefix = '')
    {
        if (isset(static::$driverCache[static::DRIVER])) {
            $driver = static::$driverCache[static::DRIVER];
        } else {
            static::$driverCache[static::DRIVER] = $driver = $this->getDriver();
        }

        return new Database($name, $prefix, $driver);
    }

    /**
     * @return Driver
     */
    public function getDriver(): Driver
    {
        $config = self::$config[static::DRIVER];
        if (!isset($this->driver)) {
            $class = $config['driver'];

            $this->driver = new $class([
                'connection' => $config['conn'],
                'username'   => $config['user'],
                'password'   => $config['pass'],
                'options'    => []
            ]);
        }

        if (self::$config['debug']) {
            $this->driver->setProfiling(true);
            $this->driver->setLogger(new TestLogger());
        }

        return $this->driver;
    }

    /**
     * @param Database $db
     */
    protected function enableProfiling(Database $db)
    {
        $db->getDriver()->setProfiling(true);
        $db->getDriver()->setLogger(new TestLogger());
    }

    /**
     * @param Database|null $database
     */
    protected function dropDatabase(Database $database = null)
    {
        if (empty($database)) {
            return;
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();

            foreach ($schema->getForeignKeys() as $foreign) {
                $schema->dropForeignKey($foreign->getColumns());
            }

            $schema->save(Handler::DROP_FOREIGN_KEYS);
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

    protected function assertSameAsInDB(AbstractTable $current)
    {
        $source = $current->getState();
        $target = $this->fetchSchema($current)->getState();

        // tesing changes
        $this->assertSame(
            $source->getName(),
            $target->getName(),
            'Table name changed'
        );

        $this->assertSame(
            $source->getPrimaryKeys(),
            $target->getPrimaryKeys(),
            'Primary keys changed'
        );

        $this->assertSame(
            count($source->getColumns()),
            count($target->getColumns()),
            'Column number has changed'
        );

        $this->assertSame(
            count($source->getIndexes()),
            count($target->getIndexes()),
            'Index number has changed'
        );

        $this->assertSame(
            count($source->getForeignKeys()),
            count($target->getForeignKeys()),
            'FK number has changed'
        );

        // columns

        foreach ($source->getColumns() as $column) {
            $this->assertTrue(
                $target->hasColumn($column->getName()),
                "Column {$column} has been removed"
            );

            $this->compareColumns($column, $target->findColumn($column->getName()));
        }

        foreach ($target->getColumns() as $column) {
            $this->assertTrue(
                $source->hasColumn($column->getName()),
                "Column {$column} has been added"
            );

            $this->compareColumns($column, $source->findColumn($column->getName()));
        }

        // indexes

        foreach ($source->getIndexes() as $index) {
            $this->assertTrue(
                $target->hasIndex($index->getColumns()),
                "Index {$index->getName()} has been removed"
            );

            $this->compareIndexes($index, $target->findIndex($index->getColumns()));
        }

        foreach ($target->getIndexes() as $index) {
            $this->assertTrue(
                $source->hasIndex($index->getColumns()),
                "Index {$index->getName()} has been removed"
            );

            $this->compareIndexes($index, $source->findIndex($index->getColumns()));
        }

        // FK
        foreach ($source->getForeignKeys() as $key) {
            $this->assertTrue(
                $target->hasForeignKey($key->getColumns()),
                "FK {$key->getName()} has been removed"
            );

            $this->compareFK($key, $target->findForeignKey($key->getColumns()));
        }

        foreach ($target->getForeignKeys() as $key) {
            $this->assertTrue(
                $source->hasForeignKey($key->getColumns()),
                "FK {$key->getName()} has been removed"
            );

            $this->compareFK($key, $source->findForeignKey($key->getColumns()));
        }

        // everything else
        $comparator = new Comparator(
            $current->getState(),
            $this->schema($current->getName())->getState()
        );

        if ($comparator->hasChanges()) {
            $this->fail($this->makeMessage($current->getName(), $comparator));
        }
    }

    protected function compareColumns(AbstractColumn $a, AbstractColumn $b)
    {
        $this->assertSame(
            $a->getInternalType(),
            $b->getInternalType(),
            "Column {$a} type has been changed"
        );

        $this->assertSame(
            $a->getScale(),
            $b->getScale(),
            "Column {$a} scale has been changed"
        );

        $this->assertSame(
            $a->getPrecision(),
            $b->getPrecision(),
            "Column {$a} precision has been changed"
        );

        $this->assertSame(
            $a->getEnumValues(),
            $b->getEnumValues(),
            "Column {$a} enum values has been changed"
        );

        $this->assertTrue(
            $a->compare($b),
            "Column {$a} has been changed"
        );
    }

    protected function compareIndexes(AbstractIndex $a, AbstractIndex $b)
    {
        $this->assertSame(
            $a->getColumns(),
            $b->getColumns(),
            "Index {$a->getName()} columns has been changed"
        );

        $this->assertSame(
            $a->isUnique(),
            $b->isUnique(),
            "Index {$a->getName()} uniquness has been changed"
        );

        $this->assertTrue(
            $a->compare($b),
            "Index {$a->getName()} has been changed"
        );
    }

    protected function compareFK(AbstractForeignKey $a, AbstractForeignKey $b)
    {
        $this->assertSame(
            $a->getColumns(),
            $b->getColumns(),
            "FK {$a->getName()} column has been changed"
        );

        $this->assertSame(
            $a->getForeignKeys(),
            $b->getForeignKeys(),
            "FK {$a->getName()} table has been changed"
        );

        $this->assertSame(
            $a->getForeignKeys(),
            $b->getForeignKeys(),
            "FK {$a->getName()} fk has been changed"
        );

        $this->assertSame(
            $a->getDeleteRule(),
            $b->getDeleteRule(),
            "FK {$a->getName()} delete rule has been changed"
        );

        $this->assertSame(
            $a->getUpdateRule(),
            $b->getUpdateRule(),
            "FK {$a->getName()} update rule has been changed"
        );

        $this->assertTrue(
            $a->compare($b),
            "FK {$a->getName()} has been changed"
        );
    }

    /**
     * @param AbstractTable $table
     * @return AbstractTable
     */
    protected function fetchSchema(AbstractTable $table): AbstractTable
    {
        return $this->schema($table->getName());
    }

    protected function makeMessage(string $table, Comparator $comparator)
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

        if ($comparator->droppedForeignKeys()) {
            return "Table '{$table}' not synced, FKs are missing.";
        }

        if ($comparator->addedForeignKeys()) {
            return "Table '{$table}' not synced, new FKs found.";
        }


        return "Table '{$table}' not synced, no idea why, add more messages :P";
    }
}


class TestLogger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = [])
    {
        if ($level == LogLevel::ERROR) {
            echo " \n! \033[31m" . $message . "\033[0m";
        } elseif ($level == LogLevel::ALERT) {
            echo " \n! \033[35m" . $message . "\033[0m";
        } elseif (strpos($message, 'SHOW') === 0) {
            echo " \n> \033[34m" . $message . "\033[0m";
        } else {
            if (strpos($message, 'SELECT') === 0) {
                echo " \n> \033[32m" . $message . "\033[0m";
            } else {
                echo " \n> \033[33m" . $message . "\033[0m";
            }
        }
    }
}