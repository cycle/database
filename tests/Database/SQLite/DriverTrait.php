<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\SQLite;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Spiral\Core\Container;
use Spiral\Database\Drivers\SQLite\SQLiteDriver;
use Spiral\Database\Entities\Driver;

trait DriverTrait
{
    private $driver;

    public function setUp()
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped(
                'The SQLite PDO extension is not available.'
            );
        }

        parent::setUp();
    }

    public function getDriver(ContainerInterface $container = null): Driver
    {
        if (!isset($this->driver)) {
            $this->driver = new SQLiteDriver(
                'sqlite',
                [
                    'connection' => 'sqlite::memory:',
                    'username'   => 'sqlite',
                    'password'   => '',
                    'options'    => []
                ],
                $container ?? new Container()
            );
        }

        $driver = $this->driver;
        $this->assertSame('sqlite', $driver->getName());

        if (static::PROFILING) {
            $driver->setProfiling(static::PROFILING)->setLogger(new class implements LoggerInterface
            {
                use LoggerTrait;

                public function log($level, $message, array $context = [])
                {
                    if ($level == LogLevel::ERROR) {
                        echo " \n! \033[31m" . $message . "\033[0m";
                    } elseif ($level == LogLevel::ALERT) {
                        echo " \n! \033[35m" . $message . "\033[0m";
                    } elseif (strpos($message, 'PRAGMA') === 0) {
                        echo " \n> \033[34m" . $message . "\033[0m";
                    } else {
                        if (strpos($message, 'SELECT') === 0) {
                            echo " \n> \033[32m" . $message . "\033[0m";
                        } else {
                            echo " \n> \033[33m" . $message . "\033[0m";
                        }
                    }
                }
            });
        }

        return $driver;
    }

    protected function driverID(): string
    {
        return 'sqlite';
    }
}