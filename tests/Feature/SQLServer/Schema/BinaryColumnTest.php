<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\SQLServer\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Injection\Parameter;
use Cycle\Testing\Database\Assert\SchemaAssert;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Test;

final class BinaryColumnTest extends DatabaseTestCase
{
    #[Test]
    public function testBinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        Assert::same($schema->column('binary_data')->getSize(), 0);
    }

    #[Test]
    public function testBinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->binary('binary_data', size: 16);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        Assert::same($schema->column('binary_data')->getSize(), 16);
    }

    #[Test]
    public function testLobParameterKeepsBinaryBytes(): void
    {
        $this->makeTable('table', ['id' => 'primary', 'binary_data' => 'binary, nullable']);
        $bytes = "\x00\xFF\x80binary\x00";

        $this->database->table('table')->insertOne(['binary_data' => new Parameter($bytes, \PDO::PARAM_LOB)]);

        Assert::same($this->database->select('binary_data')->from('table')->fetchAll()[0]['binary_data'], $bytes);
    }

    #[Test]
    public function testVarbinary(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data');
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        Assert::same($schema->column('binary_data')->getSize(), 0);
    }

    #[Test]
    public function testVarbinaryWithSize(): void
    {
        $schema = $this->schema('table');

        $schema->primary('id');
        $schema->varbinary('binary_data', size: 16);
        $schema->save(Handler::DO_ALL);

        SchemaAssert::sameAsInDb($schema);

        Assert::same($schema->column('binary_data')->getInternalType(), 'varbinary');
        Assert::same($schema->column('binary_data')->getSize(), 16);
    }
}
