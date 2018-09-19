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
use Spiral\Core\Container;
use Spiral\Database\Database;
use Spiral\Database\Driver\AbstractHandler;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Schema\AbstractTable;
use Spiral\Database\Schema\StateComparator;

abstract class BaseTest extends TestCase
{
    public static $config;
    public const DRIVER = null;

    private static $driverCache = [];
    private static $pdo;

    /** @var Driver */
    private $driver;

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

        return new Database($driver, $name, $prefix);
    }

    /**
     * @return Driver
     */
    public function getDriver(): Driver
    {
        $config = self::$config[static::DRIVER];
        if (!isset($this->driver)) {
            $class = $config['driver'];

            $this->driver = new $class(
                'mysql',
                [
                    'connection' => $config['conn'],
                    'username'   => $config['user'],
                    'password'   => $config['pass'],
                    'options'    => []
                ],
                new Container()
            );
        }

        if (empty(static::$pdo)) {
            static::$pdo = $this->driver->getPDO();
        } else {
            $this->driver = $this->driver->withPDO(self::$pdo);
        }

        if (self::$config['debug']) {
            $this->driver->setProfiling(true)->setLogger(new TestLogger());
        }

        return $this->driver;
    }

    protected function dropDatabase(Database $database = null)
    {
        if (empty($database)) {
            return;
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();

            foreach ($schema->getForeigns() as $foreign) {
                $schema->dropForeign($foreign->getColumn());
            }

            $schema->save(AbstractHandler::DROP_FOREIGNS);
        }

        foreach ($database->getTables() as $table) {
            $schema = $table->getSchema();
            $schema->declareDropped();
            $schema->save();
        }
    }

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