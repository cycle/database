<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

final class JsonColumnTest extends DatabaseTestCase
{
    /**
     * SQLite stores JSON as `text` and SQLServer as `varchar`: there the length can be changed.
     */
    #[Test]
    #[ExceptDriver(Driver::SQLite, Driver::SQLServer)]
    public function testColumnSizeIsIgnored(): void
    {
        $schema = $this->schema('table');
        $schema->primary('id');
        $schema->json('json_data', size: 255);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('json_data')->getSize(), 0);
    }
}
