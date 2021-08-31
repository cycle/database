<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Driver\Postgres;

use Cycle\Database\Driver\Handler;

/**
 * @group driver
 * @group driver-postgres
 */
class IsolationTest extends \Cycle\Database\Tests\IsolationTest
{
    public const DRIVER = 'postgres';

    public function testCreateAndMakeReferenceInSelfScope(): void
    {
        $schema = $this->schema('prefix_', 'a');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save(Handler::DO_ALL);

        $schema = $this->schema('prefix_', 'b');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->integer('to_a');
        $schema->foreignKey(['to_a'])->references('a', ['id']);

        $this->assertSame('public.prefix_b', $schema->column('id')->getTable());
        $this->assertSame('prefix_a', $schema->foreignKey(['to_a'])->getForeignTable());

        $schema->save(Handler::DO_ALL);

        $this->assertTrue($this->schema('prefix_', 'a')->exists());
        $this->assertTrue($this->schema('prefix_', 'b')->exists());

        $foreign = $this->schema('prefix_', 'b')->foreignKey(['to_a']);

        $this->assertSame('prefix_a', $foreign->getForeignTable());
    }
}
