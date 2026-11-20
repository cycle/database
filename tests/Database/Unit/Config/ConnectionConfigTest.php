<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use PHPUnit\Framework\TestCase;

final class ConnectionConfigTest
{
    #[\Testo\Test]
    public function testSetStateWithOnlyRequiredParameters(): void
    {
        $config = TcpConnectionConfig::__set_state(['database' => 'foo']);

        \Testo\Assert::same($config->database, 'foo');
        \Testo\Assert::same($config->host, 'localhost');
        \Testo\Assert::same($config->port, 5432);
        \Testo\Assert::same($config->options, [
            \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        \Testo\Assert::null($config->user);
        \Testo\Assert::null($config->password);
    }

    #[\Testo\Test]
    public function testSetStateException(): void
    {
        \Testo\Expect::exception(\ReflectionException::class);
        TcpConnectionConfig::__set_state([]);
    }
}
