<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Schema\CreateTableTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class CreateTableTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testMultipleColumnsWithJson(): void
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->string('name');
        $schema->enum('status', ['active', 'disabled']);
        $schema->float('balance')->defaultValue(0);
        $schema->json('data');

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertSameAsInDB($schema);

        $this->assertSame(['active', 'disabled'], $schema->column('status')->getEnumValues());
        $this->assertSame('json', $schema->column('data')->getAbstractType());
    }
}
