<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLServer;

use Cycle\Database\Config\SQLServer\DsnConnectionConfig;
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

        Assert::same($recoveredConfig->getDsn(), "sqlsrv:$dsn");
        Assert::same($recoveredConfig->getSourceString(), '*');
        Assert::same($recoveredConfig->getUsername(), $user);
        Assert::same($recoveredConfig->getPassword(), $password);
        Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
