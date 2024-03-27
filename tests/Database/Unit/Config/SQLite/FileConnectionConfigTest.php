<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\FileConnectionConfig;
use PHPUnit\Framework\TestCase;

final class FileConnectionConfigTest extends TestCase
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new FileConnectionConfig(
            database: $database = 'database',
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = var_export($config, true);

        /** @var FileConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = '.$exported.';');

        $this->assertSame("sqlite:$database", $recoveredConfig->getDsn());
        $this->assertSame($database, $recoveredConfig->getSourceString());
        $this->assertArrayHasKey($testOptionKey, $recoveredConfig->getOptions());
        $this->assertSame($testOptionValue, $recoveredConfig->getOptions()[$testOptionKey]);
    }
}
