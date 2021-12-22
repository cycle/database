<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Schema\AbstractColumn;

class SQLiteColumn extends AbstractColumn
{
    /**
     * Default timestamp expression (driver specific).
     */
    public const DATETIME_NOW = 'CURRENT_TIMESTAMP';

    /**
     * Private state related values.
     */
    public const EXCLUDE_FROM_COMPARE = [
        'userType',
        'timezone',
        'size',
    ];

    protected array $mapping = [
        //Primary sequences
        'primary'     => [
            'type'       => 'integer',
            'primaryKey' => true,
            'nullable'   => false,
        ],
        'bigPrimary'  => [
            'type'       => 'integer',
            'primaryKey' => true,
            'nullable'   => false,
        ],

        //Enum type (mapped via method)
        'enum'        => 'enum',

        //Logical types
        'boolean'     => 'integer',

        //Integer types (size can always be changed with size method), longInteger has method alias
        //bigInteger
        'integer'     => 'integer',
        'tinyInteger' => 'tinyint',
        'bigInteger'  => 'bigint',

        //String with specified length (mapped via method)
        'string'      => 'text',

        //Generic types
        'text'        => 'text',
        'tinyText'    => 'text',
        'longText'    => 'text',

        //Real types
        'double'      => 'double',
        'float'       => 'real',

        //Decimal type (mapped via method)
        'decimal'     => 'numeric',

        //Date and Time types
        'datetime'    => 'datetime',
        'date'        => 'date',
        'time'        => 'time',
        'timestamp'   => 'timestamp',

        //Binary types
        'binary'      => 'blob',
        'tinyBinary'  => 'blob',
        'longBinary'  => 'blob',

        //Additional types
        'json'        => 'text',
        'uuid'        => ['type' => 'varchar', 'size' => 36],
    ];

    protected array $reverseMapping = [
        'primary'     => [['type' => 'integer', 'primaryKey' => true]],
        'enum'        => ['enum'],
        'boolean'     => ['boolean'],
        'integer'     => ['int', 'integer', 'smallint', 'mediumint'],
        'tinyInteger' => ['tinyint'],
        'bigInteger'  => ['bigint'],
        'text'        => ['text', 'string'],
        'double'      => ['double'],
        'float'       => ['real'],
        'decimal'     => ['numeric'],
        'datetime'    => ['datetime'],
        'date'        => ['date'],
        'time'        => ['time'],
        'timestamp'   => ['timestamp'],
        'binary'      => ['blob'],
        'string'      => ['varchar'],
    ];

    /**
     * Indication that column is primary key.
     */
    protected bool $primaryKey = false;

    /**
     * DBMS specific reverse mapping must map database specific type into limited set of abstract
     * types.
     *
     * @psalm-return non-empty-string
     */
    public function getAbstractType(): string
    {
        if ($this->primaryKey && $this->type === 'integer') {
            return 'primary';
        }

        return parent::getAbstractType();
    }

    /**
     * @psalm-return non-empty-string
     */
    public function sqlStatement(DriverInterface $driver): string
    {
        $statement = parent::sqlStatement($driver);
        if ($this->getAbstractType() !== 'enum') {
            return $statement;
        }

        $enumValues = [];
        foreach ($this->enumValues as $value) {
            $enumValues[] = $driver->quote($value);
        }

        $quoted = $driver->identifier($this->name);

        return "$statement CHECK ({$quoted} IN (" . implode(', ', $enumValues) . '))';
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public static function createInstance(
        string $table,
        array $schema,
        \DateTimeZone $timezone = null
    ): self {
        $column = new self($table, $schema['name'], $timezone);

        $column->nullable = !$schema['notnull'];
        $column->type = $schema['type'];

        if ((bool)$schema['pk'] && $column->type === 'integer') {
            $column->primaryKey = true;
        }

        /*
         * Normalizing default value.
         */
        $column->defaultValue = $schema['dflt_value'];

        if (
            is_string($column->defaultValue)
            && preg_match('/^[\'""].*?[\'"]$/', $column->defaultValue)
        ) {
            $column->defaultValue = substr($column->defaultValue, 1, -1);
        }

        if (
            !preg_match(
                '/^(?P<type>[a-z]+) *(?:\((?P<options>[^\)]+)\))?/',
                $schema['type'],
                $matches
            )
        ) {
            //No type definition included
            return $column;
        }

        // reformatted type value
        $column->type = $matches['type'];

        //Fetching size options
        if (!empty($matches['options'])) {
            $options = explode(',', $matches['options']);

            if (count($options) > 1) {
                $column->precision = (int)$options[0];
                $column->scale = (int)$options[1];
            } else {
                $column->size = (int)$options[0];
            }
        }

        if ($column->type === 'enum') {
            //Quoted column name
            $quoted = $schema['identifier'];

            foreach ($schema['table'] as $columnSchema) {
                //Looking for enum values in column definition code
                if (
                    preg_match(
                        "/{$quoted} +enum.*?CHECK *\\({$quoted} in \\((.*?)\\)\\)/i",
                        trim($columnSchema),
                        $matches
                    )
                ) {
                    $enumValues = explode(',', $matches[1]);
                    foreach ($enumValues as &$value) {
                        //Trimming values
                        if (preg_match("/^'?(.*?)'?$/", trim($value), $matches)) {
                            //In database: 'value'
                            $value = $matches[1];
                        }

                        unset($value);
                    }
                    unset($value);

                    $column->enumValues = $enumValues;
                }
            }
        }

        return $column;
    }

    protected function quoteEnum(DriverInterface $driver): string
    {
        return '';
    }
}
