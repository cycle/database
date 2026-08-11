<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * Table introspection queries are scoped by the table name only, so a same-named table in another
 * schema contributes its rows too. The constraint batches must stay correct for every row: each
 * column must resolve the CHECK (emulated enum) and DEFAULT constraints of its own table.
 *
 * @group driver
 * @group driver-sqlserver
 */
class CrossSchemaIntrospectionTest extends BaseTest
{
    public const DRIVER = 'sqlserver';

    public function testSameNamedTablesInDifferentSchemas(): void
    {
        $driver = $this->database->getDriver();

        $driver->execute('CREATE SCHEMA [intro_other]');
        $driver->execute(
            "CREATE TABLE [dbo].[intro_dup] (
                [id] int NOT NULL,
                [status] varchar(16) NOT NULL CONSTRAINT [intro_dup_status_default] DEFAULT 'active',
                CONSTRAINT [intro_dup_status_check] CHECK ([status] IN ('active', 'disabled'))
            )",
        );
        // The padding columns shift [mode] to a column id different from the one [status] has
        // in [dbo].[intro_dup].
        $driver->execute(
            "CREATE TABLE [intro_other].[intro_dup] (
                [id] int NOT NULL,
                [padding_a] int,
                [padding_b] int,
                [mode] varchar(8) NOT NULL CONSTRAINT [intro_dup_mode_default] DEFAULT 'x',
                CONSTRAINT [intro_dup_mode_check] CHECK ([mode] IN ('x', 'y'))
            )",
        );

        $schema = $driver->getSchema('intro_dup');

        $status = $schema->column('status');
        $this->assertSame('enum', $status->getAbstractType());
        $this->assertSame(['active', 'disabled'], $status->getEnumValues());
        $this->assertContains('intro_dup_status_default', $status->getConstraints());
        $this->assertContains('intro_dup_status_check', $status->getConstraints());

        $mode = $schema->column('mode');
        $this->assertSame('enum', $mode->getAbstractType());
        $this->assertSame(['x', 'y'], $mode->getEnumValues());
        $this->assertContains('intro_dup_mode_default', $mode->getConstraints());
        $this->assertContains('intro_dup_mode_check', $mode->getConstraints());
    }

    public function tearDown(): void
    {
        $driver = $this->database->getDriver();

        foreach (['[dbo].[intro_dup]', '[intro_other].[intro_dup]'] as $table) {
            try {
                $driver->execute("DROP TABLE IF EXISTS {$table}");
            } catch (StatementException) {
            }
        }

        try {
            $driver->execute('DROP SCHEMA IF EXISTS [intro_other]');
        } catch (StatementException) {
        }

        parent::tearDown();
    }
}
