<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Schema;

use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Test;

/**
 * @todo need more validations and test for:
 *       - binary, non empty string
 *       - binary, empty string
 *       - enum invalid value
 *       - decimal, invalid value
 *       - string, too long default value
 */
#[ExceptDriver(Driver::MySQL, Driver::Postgres)]
class DefaultValueTest extends DatabaseTestCase
{
    #[Test]
    public function testDefaultNullValueForInteger(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->integer('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testDefaultPositiveValueForInteger(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->integer('target')->defaultValue(\mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    //Hahahaa, Postgres 9.4 and lower
    #[Test]
    public function testDefaultNegativeValueForInteger(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->integer('target')->defaultValue(\mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());

        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDefaultNullValueForFloat(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->float('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testDefaultPositiveValueForFloat(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->float('target')->defaultValue(\mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDefaultNegativeValueForFloat(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->float('target')->defaultValue(\mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDefaultNullValueForDouble(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->double('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testDefaultPositiveValueForDouble(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->double('target')->defaultValue(\mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDefaultNegativeValueForDouble(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->double('target')->defaultValue(\mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDefaultNullValueForDecimal(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->decimal('target', 10, 10)->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testDefaultPositiveValueForDecimal(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        //@todo test with invalid default value when code is ready
        $column = $schema->decimal('target', 10, 1)->defaultValue(\mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDefaultNegativeValueForDecimal(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        //@todo test with invalid default value when code is ready
        $column = $schema->decimal('target', 10, 1)->defaultValue(\mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDefaultNullForBoolean(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->boolean('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testDefaultTrueForBoolean(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->boolean('target')->defaultValue(true);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testDefaultFalseForBoolean(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->boolean('target')->defaultValue(false);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testStringDefaultValueEmpty(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->string('target')->defaultValue('');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testStringDefaultValueNull(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->string('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testStringDefaultValueString(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->string('target')->defaultValue('string');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    //no tests for longText, tinyText due all similar
    #[Test]
    public function testTextDefaultValueEmpty(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->text('target')->defaultValue('');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testTextDefaultValueNull(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->text('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testTextDefaultValueString(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        //This WILL fail in MySQL!
        $column = $schema->text('target')->defaultValue('non empty');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testJsonDefaultValueEmpty(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        //This WILL fail in MySQL!
        $column = $schema->json('target')->defaultValue('');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testJsonDefaultValueNull(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->json('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testJsonDefaultValueString(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        //This WILL fail in MySQL!
        $column = $schema->json('target')->defaultValue('non empty');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testJsonDefaultValueArray(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->json('target')->defaultValue(['foo' => 'bar', 'baz' => 100.0]);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }

    #[Test]
    public function testEnumDefaultValueNull(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'c'])->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
        Assert::null($schema->column('target')->getDefaultValue());
    }

    #[Test]
    public function testEnumDefaultValueValid(): void
    {
        $schema = $this->schema('table');
        Assert::false($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'c'])->defaultValue('a');

        $schema->save();
        $schema = $this->schema('table');
        Assert::true($schema->exists());
        Assert::true($schema->column('target')->compare($column));
    }
}
