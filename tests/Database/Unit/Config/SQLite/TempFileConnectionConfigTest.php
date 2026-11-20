<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\TempFileConnectionConfig;
use Testo\Assert;
use Testo\Test;

#[Test]
final class TempFileConnectionConfigTest
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new TempFileConnectionConfig(
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = \var_export($config, true);

        /** @var TempFileConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        Assert::same($recoveredConfig->getDsn(), 'sqlite:');
        Assert::same($recoveredConfig->getSourceString(), '');
        Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
