<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\MySQL;

use Cycle\Database\Config\MySQL\DsnConnectionConfig;
use Testo\Assert;
use Testo\Test;

#[Test]
final class DsnConnectionConfigTest
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new DsnConnectionConfig(
            dsn: $dsn = 'dsn',
            user: $user = 'user',
            password: $password = 'password',
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = \var_export($config, true);

        /** @var DsnConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::same($recoveredConfig->getDsn(), "mysql:$dsn");
        Assert::same($recoveredConfig->getSourceString(), '*');
        Assert::same($recoveredConfig->getUsername(), $user);
        Assert::same($recoveredConfig->getPassword(), $password);
        Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }

    public function sourceStringIsTheDatabaseNamedInDsn(): void
    {
        $config = new DsnConnectionConfig('mysql:host=db;DBNAME=shop;charset=utf8mb4');

        Assert::same($config->getSourceString(), 'shop');
    }
}
