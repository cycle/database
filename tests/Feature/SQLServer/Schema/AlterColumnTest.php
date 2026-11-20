<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Feature\Common\Schema\AlterColumnTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

final class AlterColumnTest extends CommonClass
{
    #[Test]
    public function testDatetimeColumnSizeException(): void
    {
        Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->datetime('datetime', -1);
        $schema->save();
    }

    #[Test]
    public function testDatetimeColumnSize2Exception(): void
    {
        Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->datetime('datetime', 8);
        $schema->save();
    }

    #[Test]
    public function testChangeDatetimeSize(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        Assert::same($this->fetchSchema($schema)->column('datetime')->getSize(), 0);

        $schema->datetime->datetime(7);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->column('datetime')->getSize(), 7);
    }

    #[Test]
    public function testMakeEnumNotNullable(): void
    {
        $schema = $this->sampleSchema('table');
        $schema->enum('status', ['active', 'disabled'])->nullable(false)->defaultValue('active');
        $schema->save();

        $column = $this->fetchSchema($schema)->column('status');
        Assert::false($column->isNullable());
        Assert::same($column->getEnumValues(), ['active', 'disabled']);
    }

    #[Test]
    public function testChangeDecimalPrecision(): void
    {
        $schema = $this->sampleSchema('table');
        $schema->decimal('amount', 10, 2);
        $schema->save();

        $schema = $this->schema('table');
        $schema->decimal('amount', 12, 4);
        $schema->save();

        $column = $this->schema('table')->column('amount');
        Assert::same($column->getPrecision(), 12);
        Assert::same($column->getScale(), 4);
    }

    #[Test]
    public function testAddDefaultToExistingColumn(): void
    {
        $schema = $this->sampleSchema('table');
        $schema->integer('counter')->nullable(true);
        $schema->save();

        $schema = $this->schema('table');
        $schema->integer('counter')->nullable(true)->defaultValue(5);
        $schema->save();

        Assert::same($this->schema('table')->column('counter')->getDefaultValue(), 5);
    }
}
