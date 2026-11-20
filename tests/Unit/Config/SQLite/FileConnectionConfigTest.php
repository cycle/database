<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\FileConnectionConfig;
use Testo\Assert;
use Testo\Test;

#[Test]
final class FileConnectionConfigTest
{
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

        Assert::same($recoveredConfig->getDsn(), "sqlite:$database");
        Assert::same($recoveredConfig->getSourceString(), $database);
        Assert::array($recoveredConfig->getOptions())->hasKeys($testOptionKey);
        Assert::same($recoveredConfig->getOptions()[$testOptionKey], $testOptionValue);
    }
}
