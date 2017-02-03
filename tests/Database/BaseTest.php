<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database;

use Interop\Container\ContainerInterface;
use Spiral\Database\Entities\AbstractHandler;
use Spiral\Database\Entities\Database;
use Spiral\Database\Entities\Driver;
use Spiral\Database\Schemas\Prototypes\AbstractTable;
use Spiral\Database\Schemas\StateComparator;

/**
 * ATTENTION, DO NOT CONNECT TO PRODUCTION DATABASE AT ANY COST.
 */
abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    static private $driversCache = [];

    const PROFILING = ENABLE_PROFILING;

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return Database|null When non empty null will be given, for safety, for science.
     */
    protected function database(string $name = 'default', string $prefix = '')
    {
        if (isset(self::$driversCache[$this->driverID()])) {
            $driver = self::$driversCache[$this->driverID()];
        } else {
            self::$driversCache[$this->driverID()] = $driver = $this->getDriver();
        }

        return new Database($driver, $name, $prefix);
    }

    /**
     * @return Driver
     */
    abstract protected function getDriver(ContainerInterface $container = null): Driver;

    /**
     * @return string
     */
    abstract protected function driverID(): string;

    protected function dropAll(Database $database = null)
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
