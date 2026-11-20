<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\Postgres;

use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use Cycle\Database\Tests\Unit\Config\BaseConfigTest;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
final class TcpConnectionConfigTest extends BaseConfigTest
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new TcpConnectionConfig(
            database: $database = 'database',
            host: $host = 'host',
            port: $port = 3306,
            user: $user = 'user',
            password: $password = 'password',
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = \var_export($config, true);

        /** @var TcpConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::same($recoveredConfig->getDsn(), "pgsql:host=$host;port=$port;dbname=$database");
        Assert::same($recoveredConfig->getSourceString(), $database);
        Assert::same($recoveredConfig->getUsername(), $user);
        Assert::same($recoveredConfig->getPassword(), $password);
        Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }

    #[DataProvider('portDataProvider')]
    public function testPort(int|string $port, int $expected): void
    {
        $config = new TcpConnectionConfig(
            database: 'database',
            port: $port,
        );

        Assert::same($config->port, $expected);
    }
}
