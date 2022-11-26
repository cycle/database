<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use PHPUnit\Framework\TestCase;

final class MemoryConnectionConfigTest extends TestCase
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new MemoryConnectionConfig(
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = var_export($config, true);

        /** @var MemoryConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        $this->assertSame('sqlite::memory:', $recoveredConfig->getDsn());
        $this->assertSame(':memory:', $recoveredConfig->getSourceString());
        $this->assertArrayHasKey($testOptionKey, $recoveredConfig->getOptions());
        $this->assertSame($testOptionValue, $recoveredConfig->getOptions()[$testOptionKey]);
    }
}
