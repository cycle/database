<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Tests\Feature\Common\Schema\DatetimeColumnTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

#[OnlyDriver(Driver::SQLServer)]
final class DatetimeColumnTest extends CommonClass
{
    #[Test]
    public function testDatetimeWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->datetime('datetime_data', size: 6);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('datetime_data')->getInternalType(), 'datetime2');
        Assert::same($schema->column('datetime_data')->getSize(), 6);
    }
}
