<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config;

use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use PHPUnit\Framework\TestCase;

final class ConnectionConfigTest extends TestCase
{
    public function testSetStateWithOnlyRequiredParameters(): void
    {
        $config = TcpConnectionConfig::__set_state(['database' => 'foo']);

        $this->assertSame('foo', $config->database);
        $this->assertSame('localhost', $config->host);
        $this->assertSame(5432, $config->port);
        $this->assertSame([
            \PDO::ATTR_CASE             => \PDO::CASE_NATURAL,
            \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ], $config->options);
        $this->assertNull($config->user);
        $this->assertNull($config->password);
    }

    public function testSetStateException(): void
    {
        $this->expectException(\ReflectionException::class);
        TcpConnectionConfig::__set_state([]);
    }
}
