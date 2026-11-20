<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\MySQL;

use Cycle\Database\Config\MySQL\SocketConnectionConfig;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SocketConnectionConfigTest
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

        $exported = \var_export($config, true);

        /** @var SocketConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::same($recoveredConfig->getDsn(), "mysql:unix_socket=$socket;dbname=$database;charset=$charset");
        Assert::same($recoveredConfig->getSourceString(), $database);
        Assert::same($recoveredConfig->getUsername(), $user);
        Assert::same($recoveredConfig->getPassword(), $password);
        Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
