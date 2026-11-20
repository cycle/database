<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Schema;

use Cycle\Database\Tests\Feature\Common\Schema\CreateTableTest as CommonClass;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::Postgres)]
final class CreateTableTest extends CommonClass
{
    #[Test]
    public function testCreateAndDrop(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->save();

        Assert::same($schema->column('id')->getTable(), 'public.table');

        Assert::true($schema->exists());

        $schema->declareDropped();
        $schema->save();

        $schema = $this->schema('table');
        Assert::false($schema->exists());
    }
}
