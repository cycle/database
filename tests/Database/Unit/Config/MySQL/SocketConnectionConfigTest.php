<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\MySQL;

use Cycle\Database\Config\MySQL\SocketConnectionConfig;
use PHPUnit\Framework\TestCase;

final class SocketConnectionConfigTest extends TestCase
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new SocketConnectionConfig(
            database: $database = 'database',
            socket: $socket = 'socket',
            charset: $charset = 'charset',
            user: $user = 'user',
            password: $password = 'password',
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = var_export($config, true);

        /** @var SocketConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        $this->assertSame("mysql:unix_socket=$socket;dbname=$database;charset=$charset", $recoveredConfig->getDsn());
        $this->assertSame($database, $recoveredConfig->getSourceString());
        $this->assertSame($user, $recoveredConfig->getUsername());
        $this->assertSame($password, $recoveredConfig->getPassword());
        $this->assertArrayHasKey($testOptionKey, $recoveredConfig->getOptions());
        $this->assertSame($testOptionValue, $recoveredConfig->getOptions()[$testOptionKey]);
    }
}
