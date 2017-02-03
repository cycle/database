<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database;

use Spiral\Database\Entities\Database;
use Spiral\Database\Schemas\Prototypes\AbstractTable;

abstract class SchemaCreationTest extends BaseTest
{
    /**
     * @var Database
     */
    protected $database;

    public function setUp()
    {
        $this->database = $this->database();
    }

    public function tearDown()
    {
        $this->dropAll($this->database());
    }

    public function schema(string $table): AbstractTable
    {
        return $this->database->table($table)->getSchema();
    }

    public function testEmptyTable()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $this->assertSame([], $schema->getPrimaryKeys());
        $this->assertSame([], $schema->getColumns());
        $this->assertSame([], $schema->getIndexes());
        $this->assertSame([], $schema->getForeigns());
    }

    public function testSimpleCreation()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertSameAsInDB($schema);
    }

    public function testMultipleColumns()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->string('name');
        $schema->enum('status', ['active', 'disabled']);
        $schema->float('balance')->defaultValue(0);

        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());
        $this->assertSameAsInDB($schema);

        $this->assertSame(['active', 'disabled'], $schema->column('status')->getEnumValues());
    }

    public function testCreateAndDrop()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->primary('id');
        $schema->save();

        $this->assertSame('table', $schema->column('id')->getTable());

        $this->assertTrue($schema->exists());

        $schema->declareDropped();
        $schema->save();

        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());


    }

    public function testCreateNoPrimary()
    {
        $schema = $this->schema('table');
        $this->assertFalse($schema->exists());

        $schema->string('name');
        $this->assertSame([], $schema->getPrimaryKeys());
        $schema->save();

        $schema = $this->schema('table');
        $this->assertTrue($schema->exists());

        $this->assertTrue($schema->hasColumn('name'));
    }
}