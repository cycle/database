<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Schema;

use Cycle\Database\Exception\StatementException;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * Table introspection queries are scoped by the table name only, so a same-named table in another
 * schema contributes its rows too. The constraint batches must stay correct for every row: each
 * column must resolve the CHECK (emulated enum) and DEFAULT constraints of its own table.
 */
#[\Testo\Filter\Group('driver', 'driver-sqlserver')]
class CrossSchemaIntrospectionTest extends BaseTest
{
    public const DRIVER = 'sqlserver';

    #[\Testo\Test]
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
        \Testo\Assert::same($status->getAbstractType(), 'enum');
        \Testo\Assert::same($status->getEnumValues(), ['active', 'disabled']);
        \Testo\Assert::contains($status->getConstraints(), 'intro_dup_status_default');
        \Testo\Assert::contains($status->getConstraints(), 'intro_dup_status_check');

        $mode = $schema->column('mode');
        \Testo\Assert::same($mode->getAbstractType(), 'enum');
        \Testo\Assert::same($mode->getEnumValues(), ['x', 'y']);
        \Testo\Assert::contains($mode->getConstraints(), 'intro_dup_mode_default');
        \Testo\Assert::contains($mode->getConstraints(), 'intro_dup_mode_check');
    }

    #[\Testo\Lifecycle\AfterTest]
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
