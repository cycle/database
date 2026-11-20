<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Tests\Feature\Common\Schema\CreateTableTest as CommonClass;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\Attribute\OnlyDriver;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[OnlyDriver(Driver::MySQL)]
final class CreateTableTest extends CommonClass
{
    #[Test]
    public function testMultipleColumnsWithJson(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $schema->primary('id');
        $schema->string('name');
        $schema->enum('status', ['active', 'disabled']);
        $schema->float('balance')->defaultValue(0);
        $schema->json('data');

        $schema->save();

        $schema = $this->schema('table');
        Assert::true($schema->exists());
        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('status')->getEnumValues(), ['active', 'disabled']);
        Assert::same($schema->column('data')->getAbstractType(), 'json');
    }

    #[Test]
    public function testEngineIsChosenAtCreation(): void
    {
        $schema = $this->schema('table');
        $schema->setEngine('MyISAM');
        $schema->primary('id');
        $schema->save();

        Assert::same($this->schema('table')->getEngine(), 'MyISAM');
    }

    #[Test]
    public function testEngineOfAnExistingTableIsRejected(): never
    {
        $schema = $this->schema('table');
        $schema->primary('id');
        $schema->save();

        Expect::exception(SchemaException::class)
            ->withMessage('Table engine can be set only at moment of creation');

        $this->schema('table')->setEngine('MyISAM');
    }
}
