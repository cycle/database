<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\Oracle\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Schema\AbstractColumn;

class OracleColumn extends AbstractColumn
{
    /**
     * Default timestamp expression (driver specific).
     */
    public const DATETIME_NOW = 'CURRENT_TIMESTAMP';

    /**
     * {@inheritdoc}
     */
    protected $mapping = [
        //Primary sequences
        'primary'     => [
            'type'          => 'number',
            'size'          => 11,
            'autoIncrement' => true,
            'nullable'      => false,
        ],
        'bigPrimary'  => [
            'type'          => 'number',
            'size'          => 20,
            'autoIncrement' => true,
            'nullable'      => false,
        ],

        //Enum type (mapped via method)
        'enum'        => 'enum',

        //Logical types
        'boolean'     => ['type' => 'tinyint', 'size' => 1],

        //Integer types (size can always be changed with size method), longInteger has method alias
        //bigInteger
        'integer'     => ['type' => 'number', 'size' => 11],
        'tinyInteger' => ['type' => 'number', 'size' => 4],
        'bigInteger'  => ['type' => 'number', 'size' => 20],

        //String with specified length (mapped via method)
        'string'      => ['type' => 'varchar2', 'size' => 255],

        //Generic types
        'text'        => 'long',
        'tinyText'    => 'char',
        'longText'    => 'long',

        //Real types
        'double'      => 'numeric',
        'float'       => 'float',

        //Decimal type (mapped via method)
        'decimal'     => 'decimal',

        //Date and Time types
        'datetime'    => 'datetime',
        'date'        => 'date',
        'time'        => 'time',
        'timestamp'   => ['type' => 'timestamp', 'defaultValue' => null],

        //Binary types
        'binary'      => 'blob',
        'tinyBinary'  => 'blob',
        'longBinary'  => 'blob',

        //Additional types
        'json'        => 'text',
        'uuid'        => ['type' => 'varchar2', 'size' => 36],
    ];

    /**
     * {@inheritdoc}
     */
    protected $reverseMapping = [
        'primary'     => [['type' => 'int', 'autoIncrement' => true]],
        'bigPrimary'  => ['serial', ['type' => 'bigint', 'autoIncrement' => true]],
        'enum'        => ['enum'],
        'boolean'     => ['bool', 'boolean', ['type' => 'tinyint', 'size' => 1]],
        'integer'     => ['int', 'integer', 'smallint', 'mediumint'],
        'tinyInteger' => ['tinyint'],
        'bigInteger'  => ['bigint'],
        'string'      => ['varchar', 'char'],
        'text'        => ['text', 'mediumtext'],
        'tinyText'    => ['tinytext'],
        'longText'    => ['longtext'],
        'double'      => ['double'],
        'float'       => ['float', 'real'],
        'decimal'     => ['decimal'],
        'datetime'    => ['datetime'],
        'date'        => ['date'],
        'time'        => ['time'],
        'timestamp'   => ['timestamp'],
        'binary'      => ['blob', 'binary', 'varbinary'],
        'tinyBinary'  => ['tinyblob'],
        'longBinary'  => ['longblob'],
    ];

    /**
     * List of types forbids default value set.
     *
     * @var array
     */
    protected $forbiddenDefaults = [
        'text',
        'mediumtext',
        'tinytext',
        'longtext',
        'blog',
        'tinyblob',
        'longblob',
    ];

    /**
     * Column is auto incremental.
     *
     * @var bool
     */
    protected $autoIncrement = false;



    /**
     * @param string          $table  Table name.
     * @param array           $schema
     * @param DriverInterface $driver Postgres columns are bit more complex.
     * @return OracleColumn
     */
    public static function createInstance(
        string $table,
        array $schema,
        DriverInterface $driver
    ): self {
        $column = new self($table, $schema['COLUMN_NAME'], $driver->getTimezone());

        $column->type = $schema['DATA_TYPE'];
        $column->defaultValue = $schema['DATA_DEFAULT'];
        $column->nullable = $schema['NULLABLE'] === 'Y';

        if (strpos($column->type, 'char') !== false && $schema['character_maximum_length']) {
            $column->size = $schema['character_maximum_length'];
        }

        if ($column->type === 'number') {
            $column->precision = $schema['numeric_precision'];
            $column->scale = $schema['numeric_scale'];
        }

        return $column;
    }
}
