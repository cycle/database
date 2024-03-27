<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLServer;

use Cycle\Database\Config\SQLServer\DsnConnectionConfig;
use PHPUnit\Framework\TestCase;

final class DsnConnectionConfigTest extends TestCase
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new DsnConnectionConfig(
            dsn: $dsn = 'dsn',
            user: $user = 'user',
            password: $password = 'password',
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = var_export($config, true);

        /** @var DsnConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = '.$exported.';');

        $this->assertSame("sqlsrv:$dsn", $recoveredConfig->getDsn());
        $this->assertSame('*', $recoveredConfig->getSourceString());
        $this->assertSame($user, $recoveredConfig->getUsername());
        $this->assertSame($password, $recoveredConfig->getPassword());
        $this->assertArrayHasKey($testOptionKey, $recoveredConfig->getOptions());
        $this->assertSame($testOptionValue, $recoveredConfig->getOptions()[$testOptionKey]);
    }
}
