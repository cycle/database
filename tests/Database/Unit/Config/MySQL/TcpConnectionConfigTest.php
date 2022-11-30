<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\MySQL;

use Cycle\Database\Config\MySQL\TcpConnectionConfig;
use PHPUnit\Framework\TestCase;

final class TcpConnectionConfigTest extends TestCase
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new TcpConnectionConfig(
            database: $database = 'database',
            host: $host = 'host',
            port: $port = 3306,
            charset: $charset = 'charset',
            user: $user = 'user',
            password: $password = 'password',
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = var_export($config, true);

        /** @var TcpConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        $this->assertSame("mysql:host=$host;port=$port;dbname=$database;charset=$charset", $recoveredConfig->getDsn());
        $this->assertSame($database, $recoveredConfig->getSourceString());
        $this->assertSame($user, $recoveredConfig->getUsername());
        $this->assertSame($password, $recoveredConfig->getPassword());
        $this->assertArrayHasKey($testOptionKey, $recoveredConfig->getOptions());
        $this->assertSame($testOptionValue, $recoveredConfig->getOptions()[$testOptionKey]);
    }
}
