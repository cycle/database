<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Schema;

// phpcs:ignore
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Functional\Driver\Common\Schema\AlterColumnTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class AlterColumnTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testDatetimeColumnSizeException(): void
    {
        \Testo\Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->datetime('datetime', size: -1);
        $schema->save();
    }

    #[\Testo\Test]
    public function testDatetimeColumnSize2Exception(): void
    {
        \Testo\Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        $schema->datetime('datetime', 7);
        $schema->save();
    }

    #[\Testo\Test]
    public function testChangeDatetimeSize(): void
    {
        $schema = $this->sampleSchema('table');
        \Testo\Assert::true($schema->exists());

        \Testo\Assert::same($this->fetchSchema($schema)->column('datetime')->getSize(), 0);

        $schema->datetime->string(6);
        $schema->save();

        $this->assertSameAsInDB($schema);
        \Testo\Assert::same($this->fetchSchema($schema)->column('datetime')->getSize(), 6);
    }
}
