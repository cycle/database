<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Connection;

use Cycle\Testing\Database\Attribute\WrapDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Lifecycle\BeforeTest;

#[WrapDriver]
abstract class BaseConnectionTest extends DatabaseTestCase
{
    #[BeforeTest]
    public function createTable(): void
    {
        $schema = $this->database->table('table')->getSchema();
        $schema->primary('id');
        $schema->text('name');
        $schema->integer('value');
        $schema->save();
    }
}
