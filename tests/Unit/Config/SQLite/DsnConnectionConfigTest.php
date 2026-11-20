<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\DsnConnectionConfig;
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
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = \var_export($config, true);

        /** @var DsnConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::same($recoveredConfig->getDsn(), "sqlite:$dsn");
        Assert::same($recoveredConfig->getSourceString(), $dsn);
        Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
