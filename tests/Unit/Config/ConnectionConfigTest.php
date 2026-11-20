<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class ConnectionConfigTest
{
    public function testSetStateWithOnlyRequiredParameters(): void
    {
        $config = TcpConnectionConfig::__set_state(['database' => 'foo']);

        Assert::same($config->database, 'foo');
        Assert::same($config->host, 'localhost');
        Assert::same($config->port, 5432);
        Assert::same($config->options, [
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        Assert::null($config->user);
        Assert::null($config->password);
    }

    #[ExpectException(\ReflectionException::class)]
    public function testSetStateException(): void
    {
        TcpConnectionConfig::__set_state([]);
    }
}
