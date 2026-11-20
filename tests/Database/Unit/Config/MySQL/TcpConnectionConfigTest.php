<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\MySQL;

use Cycle\Database\Config\MySQL\TcpConnectionConfig;
use Cycle\Database\Tests\Unit\Config\BaseConfigTest;

final class TcpConnectionConfigTest extends BaseConfigTest
{
    #[\Testo\Test]
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

        $exported = \var_export($config, true);

        /** @var TcpConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        \Testo\Assert::same($recoveredConfig->getDsn(), "mysql:host=$host;port=$port;dbname=$database;charset=$charset");
        \Testo\Assert::same($recoveredConfig->getSourceString(), $database);
        \Testo\Assert::same($recoveredConfig->getUsername(), $user);
        \Testo\Assert::same($recoveredConfig->getPassword(), $password);
        \Testo\Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        \Testo\Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('portDataProvider')]
    public function testPort(int|string $port, int $expected): void
    {
        $config = new TcpConnectionConfig(
            database: 'database',
            port: $port,
        );

        \Testo\Assert::same($config->port, $expected);
    }
}
