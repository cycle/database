<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\Postgres;

use Cycle\Database\Config\Postgres\DsnConnectionConfig;
use PHPUnit\Framework\TestCase;

final class DsnConnectionConfigTest
{
    #[\Testo\Test]
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

        \Testo\Assert::same($recoveredConfig->getDsn(), "pgsql:$dsn");
        \Testo\Assert::same($recoveredConfig->getSourceString(), '*');
        \Testo\Assert::same($recoveredConfig->getUsername(), $user);
        \Testo\Assert::same($recoveredConfig->getPassword(), $password);
        \Testo\Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        \Testo\Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
