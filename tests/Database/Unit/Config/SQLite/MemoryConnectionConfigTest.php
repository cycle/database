<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use PHPUnit\Framework\TestCase;

final class MemoryConnectionConfigTest
{
    #[\Testo\Test]
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new MemoryConnectionConfig(
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = \var_export($config, true);

        /** @var MemoryConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        \Testo\Assert::same($recoveredConfig->getDsn(), 'sqlite::memory:');
        \Testo\Assert::same($recoveredConfig->getSourceString(), ':memory:');
        \Testo\Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        \Testo\Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
