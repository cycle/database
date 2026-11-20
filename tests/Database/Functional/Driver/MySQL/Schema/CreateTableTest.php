<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\CreateTableTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class CreateTableTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testMultipleColumnsWithJson(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $schema->primary('id');
        $schema->string('name');
        $schema->enum('status', ['active', 'disabled']);
        $schema->float('balance')->defaultValue(0);
        $schema->json('data');

        $schema->save();

        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        $this->assertSameAsInDB($schema);

        \Testo\Assert::same($schema->column('status')->getEnumValues(), ['active', 'disabled']);
        \Testo\Assert::same($schema->column('data')->getAbstractType(), 'json');
    }
}
