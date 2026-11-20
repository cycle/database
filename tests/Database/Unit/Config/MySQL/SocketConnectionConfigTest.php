<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\MySQL;

use Cycle\Database\Config\MySQL\SocketConnectionConfig;
use PHPUnit\Framework\TestCase;

final class SocketConnectionConfigTest
{
    #[\Testo\Test]
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

        \Testo\Assert::same($recoveredConfig->getDsn(), "mysql:unix_socket=$socket;dbname=$database;charset=$charset");
        \Testo\Assert::same($recoveredConfig->getSourceString(), $database);
        \Testo\Assert::same($recoveredConfig->getUsername(), $user);
        \Testo\Assert::same($recoveredConfig->getPassword(), $password);
        \Testo\Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        \Testo\Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
