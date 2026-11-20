<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\FileConnectionConfig;
use PHPUnit\Framework\TestCase;

final class FileConnectionConfigTest
{
    #[\Testo\Test]
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new FileConnectionConfig(
            database: $database = 'database',
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = \var_export($config, true);

        /** @var FileConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        \Testo\Assert::same($recoveredConfig->getDsn(), "sqlite:$database");
        \Testo\Assert::same($recoveredConfig->getSourceString(), $database);
        \Testo\Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        \Testo\Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
