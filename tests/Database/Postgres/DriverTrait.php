<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\Postgres;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Spiral\Core\Container;
use Spiral\Database\Drivers\Postgres\PostgresDriver;
use Spiral\Database\Entities\Driver;

trait DriverTrait
{
    private $driver;

    private static $pdo;

    public function setUp()
    {
        if (!in_array('pgsql', \PDO::getAvailableDrivers())) {
            $this->markTestSkipped(
                'The Postgres PDO extension is not available.'
            );
        }

        parent::setUp();
    }

    public function getDriver(ContainerInterface $container = null): Driver
    {
        if (!isset($this->driver)) {
            $this->driver = new PostgresDriver(
                'postgres',
                [
                    'connection' => 'pgsql:host=127.0.0.1;dbname=spiral',
                    'username'   => 'postgres',
                    'password'   => 'postgres',
                    'options'    => []
                ],
                $container ?? new Container()
            );
        }

        if (empty(self::$pdo)) {
            self::$pdo = $this->driver->getPDO();
        } else {
            $this->driver = $this->driver->withPDO(self::$pdo);
        }

        $driver = $this->driver;


        $this->assertSame('postgres', $driver->getName());

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
                    } elseif (
                        strpos($message, 'pg_') || strpos($message, 'tc.')
                    ) {
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
        return 'postgres';
    }
}