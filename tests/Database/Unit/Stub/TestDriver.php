<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Stub;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\PDOInterface;
use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Driver\SQLite\SQLiteHandler;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\Query\QueryBuilder;
use PDO;

class TestDriver extends Driver
{
    protected ?PDOInterface $pdoMock = null;

    protected function mapException(\Throwable $exception, string $query): StatementException
    {
        throw $exception;
    }

    public function getType(): string
    {
        return 'test';
    }

    public static function create(DriverConfig $config): Driver
    {
        return new self(
            $config,
            new SQLiteHandler(),
            new SQLiteCompiler('""'),
            QueryBuilder::defaultBuilder()
        );
    }

    public static function createWith(
        DriverConfig $config,
        HandlerInterface $handler,
        BuilderInterface $builder,
        ?PDOInterface $pdoMock = null
    ): Driver {
        $driver = new self(
            $config,
            $handler,
            new SQLiteCompiler('""'),
            $builder
        );

        $driver->pdoMock = $pdoMock;

        return $driver;
    }

    protected function getPDO(): PDO|PDOInterface
    {
        return $this->pdoMock ?? parent::getPDO();
    }
}
