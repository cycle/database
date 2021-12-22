<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\DefaultValueException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Schema\AbstractColumn;

/**
 * Attention! You can use only one timestamp or datetime with DATETIME_NOW setting! Thought, it will
 * work on multiple fields with MySQL 5.6.6+ version.
 */
class MySQLColumn extends AbstractColumn
{
    /**
     * Default timestamp expression (driver specific).
     */
    public const DATETIME_NOW = 'CURRENT_TIMESTAMP';

    protected array $mapping = [
        //Primary sequences
        'primary'     => [
            'type'          => 'int',
            'size'          => 11,
            'autoIncrement' => true,
            'nullable'      => false,
        ],
        'bigPrimary'  => [
            'type'          => 'bigint',
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
        'integer'     => ['type' => 'int', 'size' => 11],
        'tinyInteger' => ['type' => 'tinyint', 'size' => 4],
        'bigInteger'  => ['type' => 'bigint', 'size' => 20],

        //String with specified length (mapped via method)
        'string'      => ['type' => 'varchar', 'size' => 255],

        //Generic types
        'text'        => 'text',
        'tinyText'    => 'tinytext',
        'longText'    => 'longtext',

        //Real types
        'double'      => 'double',
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
        'tinyBinary'  => 'tinyblob',
        'longBinary'  => 'longblob',

        //Additional types
        'json'        => 'text',
        'uuid'        => ['type' => 'varchar', 'size' => 36],
    ];

    protected array $reverseMapping = [
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
     */
    protected array $forbiddenDefaults = [
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
     */
    protected bool $autoIncrement = false;

    /**
     * @psalm-return non-empty-string
     */
    public function sqlStatement(DriverInterface $driver): string
    {
        $defaultValue = $this->defaultValue;

        if (\in_array($this->type, $this->forbiddenDefaults, true)) {
            //Flushing default value for forbidden types
            $this->defaultValue = null;
        }

        $statement = parent::sqlStatement($driver);

        $this->defaultValue = $defaultValue;
        if ($this->autoIncrement) {
            return "{$statement} AUTO_INCREMENT";
        }

        return $statement;
    }

    /**
     * @psalm-param non-empty-string $table
     */
    public static function createInstance(string $table, array $schema, \DateTimeZone $timezone = null): self
    {
        $column = new self($table, $schema['Field'], $timezone);

        $column->type = $schema['Type'];
        $column->nullable = strtolower($schema['Null']) === 'yes';
        $column->defaultValue = $schema['Default'];
        $column->autoIncrement = stripos($schema['Extra'], 'auto_increment') !== false;

        if (
            !preg_match(
                '/^(?P<type>[a-z]+)(?:\((?P<options>[^\)]+)\))?/',
                $column->type,
                $matches
            )
        ) {
            //No extra definitions
            return $column;
        }

        $column->type = $matches['type'];

        $options = [];
        if (!empty($matches['options'])) {
            $options = explode(',', $matches['options']);

            if (count($options) > 1) {
                $column->precision = (int)$options[0];
                $column->scale = (int)$options[1];
            } else {
                $column->size = (int)$options[0];
            }
        }

        // since 8.0 database does not provide size for some of the columns
        if ($column->size === 0) {
            switch ($column->type) {
                case 'int':
                    $column->size = 11;
                    break;
                case 'bigint':
                    $column->size = 20;
                    break;
                case 'tinyint':
                    if ($column->size !== 1) {
                        $column->size = 4;
                    }
            }
        }

        //Fetching enum values
        if ($options !== [] && $column->getAbstractType() === 'enum') {
            $column->enumValues = array_map(static fn ($value) => trim($value, $value[0]), $options);

            return $column;
        }

        //Default value conversions
        if ($column->type === 'bit' && $column->hasDefaultValue()) {
            //Cutting b\ and '
            $column->defaultValue = new Fragment($column->defaultValue);
        }

        if (
            $column->defaultValue === '0000-00-00 00:00:00'
            && $column->getAbstractType() === 'timestamp'
        ) {
            //Normalizing default value for timestamps
            $column->defaultValue = 0;
        }

        return $column;
    }

    /**
     * Ensure that datetime fields are correctly formatted.
     *
     * @psalm-param non-empty-string $type
     *
     * @throws DefaultValueException
     */
    protected function formatDatetime(
        string $type,
        string|int|\DateTimeInterface $value
    ): \DateTimeInterface|FragmentInterface|string {
        if ($value === 'current_timestamp()') {
            $value = self::DATETIME_NOW;
        }

        return parent::formatDatetime($type, $value);
    }
}
