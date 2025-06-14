<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

// phpcs:ignore
use Cycle\Database\Driver\SQLServer\Query\SQLServerUpsertQuery;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
final class UpsertQueryTest extends BaseTest
{
    public const DRIVER = 'sqlserver';

    public function testQueryInstance(): void
    {
        $this->assertInstanceOf(SQLServerUpsertQuery::class, $this->database->upsert());
        $this->assertInstanceOf(SQLServerUpsertQuery::class, $this->database->table->upsert());
    }

    public function testCompileUpsertQueryThrowsException(): void
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage('Upsert behaviour is not supported by SQLServer');

        $this->db()->upsert('table')->values([])->__toString();
    }
}
