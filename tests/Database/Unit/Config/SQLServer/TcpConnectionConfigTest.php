<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Config\SQLServer;

use Cycle\Database\Config\SQLServer\TcpConnectionConfig;
use PHPUnit\Framework\TestCase;

final class TcpConnectionConfigTest extends TestCase
{
    public function testSetState(): void
    {
        $testOptionKey = 'option-1';
        $testOptionValue = 'option-2';
        $config = new TcpConnectionConfig(
            database: $database = 'database',
            host: $host = 'host',
            port: $port = 3306,
            app: $app = 'app',
            pooling: $pooling = true,
            encrypt: $encrypt = false,
            failover: $failover = 'failover',
            timeout: $timeout = 123,
            mars: $mars = true,
            quoted: $quoted = false,
            traceFile: $traceFile = 'traceFile',
            trace: $trace = true,
            isolation: $isolation = 456,
            trustServerCertificate: $trustServerCertificate = false,
            wsid: $wsid = 'wsid',
            user: $user = 'user',
            password: $password = 'password',
            options: [$testOptionKey => $testOptionValue],
        );

        $exported = var_export($config, true);

        /** @var TcpConnectionConfig $recoveredConfig */
        eval('$recoveredConfig = ' . $exported . ';');

        $this->assertSame(
            sprintf(
                'sqlsrv:APP=%s;ConnectionPooling=%s;Database=%s;Encrypt=%s;Failover_Partner=%s;LoginTimeout=%s;MultipleActiveResultSets=%s;QuotedId=%s;Server=%s,%s;TraceFile=%s;TraceOn=%s;TransactionIsolation=%s;TrustServerCertificate=%s;WSID=%s',
                $app,
                (int) $pooling,
                $database,
                (int) $encrypt,
                $failover,
                $timeout,
                (int) $mars,
                (int) $quoted,
                $host,
                $port,
                $traceFile,
                (int) $trace,
                $isolation,
                (int) $trustServerCertificate,
                $wsid,
            ),
            $recoveredConfig->getDsn(),
        );
        $this->assertSame($database, $recoveredConfig->getSourceString());
        $this->assertSame($user, $recoveredConfig->getUsername());
        $this->assertSame($password, $recoveredConfig->getPassword());
        $this->assertArrayHasKey($testOptionKey, $recoveredConfig->getOptions());
        $this->assertSame($testOptionValue, $recoveredConfig->getOptions()[$testOptionKey]);
    }
}
