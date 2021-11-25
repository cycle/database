<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\Driver;
use Cycle\Database\Driver\SQLite\SQLiteCompiler;
use Cycle\Database\Driver\SQLite\SQLiteHandler;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\Query\QueryBuilder;

class TestDriver extends Driver
{

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
}
