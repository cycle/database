<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\TempFileConnectionConfig;
use PHPUnit\Framework\TestCase;

final class TempFileConnectionConfigTest
{
    #[\Testo\Test]
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

        \Testo\Assert::same($recoveredConfig->getDsn(), 'sqlite:');
        \Testo\Assert::same($recoveredConfig->getSourceString(), '');
        \Testo\Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        \Testo\Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
