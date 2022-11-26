<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLite;

use Cycle\Database\Config\SQLite\TempFileConnectionConfig;
use PHPUnit\Framework\TestCase;

final class TempFileConnectionConfigTest extends TestCase
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new TempFileConnectionConfig(
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = var_export($config, true);

        /** @var TempFileConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        $this->assertSame('sqlite:', $recoveredConfig->getDsn());
        $this->assertSame('', $recoveredConfig->getSourceString());
        $this->assertArrayHasKey($testOptionKey, $recoveredConfig->getOptions());
        $this->assertSame($testOptionValue, $recoveredConfig->getOptions()[$testOptionKey]);
    }
}
