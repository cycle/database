<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Schema;

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

        $schema->datetime('datetime', size: -1);
        $schema->save();
    }

    #[Test]
    public function testDatetimeColumnSize2Exception(): void
    {
        Expect::exception(SchemaException::class);
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        $schema->datetime('datetime', 7);
        $schema->save();
    }

    #[Test]
    public function testChangeDatetimeSize(): void
    {
        $schema = $this->sampleSchema('table');
        Assert::true($schema->exists());

        Assert::same($this->fetchSchema($schema)->column('datetime')->getSize(), 0);

        $schema->datetime->string(6);
        $schema->save();

        SchemaAssert::sameAsInDb($schema);
        Assert::same($this->fetchSchema($schema)->column('datetime')->getSize(), 6);
    }
}
