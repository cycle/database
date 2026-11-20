<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\DsnConnectionConfig;
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
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = \var_export($config, true);

        /** @var DsnConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        \Testo\Assert::same($recoveredConfig->getDsn(), "sqlite:$dsn");
        \Testo\Assert::same($recoveredConfig->getSourceString(), $dsn);
        \Testo\Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        \Testo\Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
