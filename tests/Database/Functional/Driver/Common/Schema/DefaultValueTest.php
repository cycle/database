<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @todo need more validations and test for:
 *       - binary, non empty string
 *       - binary, empty string
 *       - enum invalid value
 *       - decimal, invalid value
 *       - string, too long default value
 */
abstract class DefaultValueTest extends BaseTest
{
    #[\Testo\Test]
    public function testDefaultNullValueForInteger(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->integer('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testDefaultPositiveValueForInteger(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->integer('target')->defaultValue(\mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    //Hahahaa, Postgres 9.4 and lower
    #[\Testo\Test]
    public function testDefaultNegativeValueForInteger(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->integer('target')->defaultValue(\mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());

        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testDefaultNullValueForFloat(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->float('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testDefaultPositiveValueForFloat(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->float('target')->defaultValue(\mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testDefaultNegativeValueForFloat(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->float('target')->defaultValue(\mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testDefaultNullValueForDouble(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->double('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testDefaultPositiveValueForDouble(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->double('target')->defaultValue(\mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testDefaultNegativeValueForDouble(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->double('target')->defaultValue(\mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testDefaultNullValueForDecimal(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->decimal('target', 10, 10)->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testDefaultPositiveValueForDecimal(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        //@todo test with invalid default value when code is ready
        $column = $schema->decimal('target', 10, 1)->defaultValue(\mt_rand(0, 100000));

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testDefaultNegativeValueForDecimal(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        //@todo test with invalid default value when code is ready
        $column = $schema->decimal('target', 10, 1)->defaultValue(\mt_rand(-100000, 0));

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testDefaultNullForBoolean(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->boolean('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testDefaultTrueForBoolean(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->boolean('target')->defaultValue(true);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testDefaultFalseForBoolean(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->boolean('target')->defaultValue(false);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testStringDefaultValueEmpty(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->string('target')->defaultValue('');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testStringDefaultValueNull(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->string('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testStringDefaultValueString(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->string('target')->defaultValue('string');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    //no tests for longText, tinyText due all similar
    #[\Testo\Test]
    public function testTextDefaultValueEmpty(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->text('target')->defaultValue('');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testTextDefaultValueNull(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->text('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testTextDefaultValueString(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        //This WILL fail in MySQL!
        $column = $schema->text('target')->defaultValue('non empty');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testJsonDefaultValueEmpty(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        //This WILL fail in MySQL!
        $column = $schema->json('target')->defaultValue('');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testJsonDefaultValueNull(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->json('target')->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testJsonDefaultValueString(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        //This WILL fail in MySQL!
        $column = $schema->json('target')->defaultValue('non empty');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testJsonDefaultValueArray(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->json('target')->defaultValue(['foo' => 'bar', 'baz' => 100.0]);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }

    #[\Testo\Test]
    public function testEnumDefaultValueNull(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'c'])->defaultValue(null);

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
        \Testo\Assert::null($schema->column('target')->getDefaultValue());
    }

    #[\Testo\Test]
    public function testEnumDefaultValueValid(): void
    {
        $schema = $this->schema('table');
        \Testo\Assert::false($schema->exists());

        $column = $schema->enum('target', ['a', 'b', 'c'])->defaultValue('a');

        $schema->save();
        $schema = $this->schema('table');
        \Testo\Assert::true($schema->exists());
        \Testo\Assert::true($schema->column('target')->compare($column));
    }
}
