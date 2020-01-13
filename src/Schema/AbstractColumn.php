<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Schema;

use DateTimeImmutable;
use Spiral\Database\ColumnInterface;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Exception\DefaultValueException;
use Spiral\Database\Exception\SchemaException;
use Spiral\Database\Injection\Fragment;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Query\QueryParameters;
use Spiral\Database\Schema\Traits\ElementTrait;

/**
 * Abstract column schema with read (see ColumnInterface) and write abilities. Must be implemented
 * by driver to support DBMS specific syntax and creation rules.
 *
 * Shortcuts for various column types:
 *
 * @method AbstractColumn|$this primary()
 * @method AbstractColumn|$this bigPrimary()
 * @method AbstractColumn|$this boolean()
 * @method AbstractColumn|$this integer()
 * @method AbstractColumn|$this tinyInteger()
 * @method AbstractColumn|$this bigInteger()
 * @method AbstractColumn|$this text()
 * @method AbstractColumn|$this tinyText()
 * @method AbstractColumn|$this longText()
 * @method AbstractColumn|$this double()
 * @method AbstractColumn|$this float()
 * @method AbstractColumn|$this datetime()
 * @method AbstractColumn|$this date()
 * @method AbstractColumn|$this time()
 * @method AbstractColumn|$this timestamp()
 * @method AbstractColumn|$this binary()
 * @method AbstractColumn|$this tinyBinary()
 * @method AbstractColumn|$this longBinary()
 * @method AbstractColumn|$this json()
 * @method AbstractColumn|$this uuid()
 */
abstract class AbstractColumn implements ColumnInterface, ElementInterface
{
    use ElementTrait;

    /**
     * Default timestamp expression (driver specific).
     */
    public const DATETIME_NOW = 'CURRENT_TIMESTAMP';

    /**
     * Value to be excluded from comparision.
     */
    public const EXCLUDE_FROM_COMPARE = ['timezone', 'userType'];

    /**
     * Normalization for time and dates.
     */
    public const DATE_FORMAT = 'Y-m-d';
    public const TIME_FORMAT = 'H:i:s';

    /**
     * Mapping between abstract type and internal database type with it's options. Multiple abstract
     * types can map into one database type, this implementation allows us to equalize two columns
     * if they have different abstract types but same database one. Must be declared by DBMS
     * specific implementation.
     *
     * Example:
     * integer => array('type' => 'int', 'size' => 1),
     * boolean => array('type' => 'tinyint', 'size' => 1)
     *
     * @internal
     *
     * @var array
     */
    protected $mapping = [
        //Primary sequences
        'primary'     => null,
        'bigPrimary'  => null,

        //Enum type (mapped via method)
        'enum'        => null,

        //Logical types
        'boolean'     => null,

        //Integer types (size can always be changed with size method), longInteger has method alias
        //bigInteger
        'integer'     => null,
        'tinyInteger' => null,
        'bigInteger'  => null,

        //String with specified length (mapped via method)
        'string'      => null,

        //Generic types
        'text'        => null,
        'tinyText'    => null,
        'longText'    => null,

        //Real types
        'double'      => null,
        'float'       => null,

        //Decimal type (mapped via method)
        'decimal'     => null,

        //Date and Time types
        'datetime'    => null,
        'date'        => null,
        'time'        => null,
        'timestamp'   => null,

        //Binary types
        'binary'      => null,
        'tinyBinary'  => null,
        'longBinary'  => null,

        //Additional types
        'json'        => null,
    ];

    /**
     * Reverse mapping is responsible for generating abstact type based on database type and it's
     * options. Multiple database types can be mapped into one abstract type.
     *
     * @internal
     *
     * @var array
     */
    protected $reverseMapping = [
        'primary'     => [],
        'bigPrimary'  => [],
        'enum'        => [],
        'boolean'     => [],
        'integer'     => [],
        'tinyInteger' => [],
        'bigInteger'  => [],
        'string'      => [],
        'text'        => [],
        'tinyText'    => [],
        'longText'    => [],
        'double'      => [],
        'float'       => [],
        'decimal'     => [],
        'datetime'    => [],
        'date'        => [],
        'time'        => [],
        'timestamp'   => [],
        'binary'      => [],
        'tinyBinary'  => [],
        'longBinary'  => [],
        'json'        => [],
    ];

    /**
     * User defined type. Only until actual mapping.
     *
     * @var string|null
     */
    protected $userType = null;

    /**
     * DBMS specific column type.
     *
     * @var string
     */
    protected $type = '';

    /**
     * @var \DateTimeZone
     */
    protected $timezone = null;

    /**
     * Indicates that column can contain null values.
     *
     * @var bool
     */
    protected $nullable = true;

    /**
     * Default column value, may not be applied to some datatypes (for example to primary keys),
     * should follow type size and other options.
     *
     * @var mixed
     */
    protected $defaultValue = null;

    /**
     * Column type size, can have different meanings for different datatypes.
     *
     * @var int
     */
    protected $size = 0;

    /**
     * Precision of column, applied only for "decimal" type.
     *
     * @var int
     */
    protected $precision = 0;

    /**
     * Scale of column, applied only for "decimal" type.
     *
     * @var int
     */
    protected $scale = 0;

    /**
     * List of allowed enum values.
     *
     * @var array
     */
    protected $enumValues = [];

    /**
     * Abstract type aliases (for consistency).
     *
     * @var array
     */
    private $aliases = [
        'int'            => 'integer',
        'bigint'         => 'bigInteger',
        'incremental'    => 'primary',
        'bigIncremental' => 'bigPrimary',
        'bool'           => 'boolean',
        'blob'           => 'binary',
    ];

    /**
     * Association list between abstract types and native PHP types. Every non listed type will be
     * converted into string.
     *
     * @internal
     *
     * @var array
     */
    private $phpMapping = [
        self::INT   => ['primary', 'bigPrimary', 'integer', 'tinyInteger', 'bigInteger'],
        self::BOOL  => ['boolean'],
        self::FLOAT => ['double', 'float', 'decimal'],
    ];

    /**
     * @param string        $table
     * @param string        $name
     * @param \DateTimeZone $timezone
     */
    public function __construct(string $table, string $name, \DateTimeZone $timezone = null)
    {
        $this->table = $table;
        $this->name = $name;
        $this->timezone = $timezone ?? new \DateTimeZone(date_default_timezone_get());
    }

    /**
     * Shortcut for AbstractColumn->type() method.
     *
     * @param string $type      Abstract type.
     * @param array  $arguments Not used.
     * @return self
     */
    public function __call(string $type, array $arguments = []): AbstractColumn
    {
        return $this->type($type);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->table . '.' . $this->getName();
    }

    /**
     * Simplified way to dump information.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $column = [
            'name' => $this->name,
            'type' => [
                'database' => $this->type,
                'schema'   => $this->getAbstractType(),
                'php'      => $this->getType(),
            ],
        ];

        if (!empty($this->size)) {
            $column['size'] = $this->size;
        }

        if ($this->nullable) {
            $column['nullable'] = true;
        }

        if ($this->defaultValue !== null) {
            $column['defaultValue'] = $this->getDefaultValue();
        }

        if ($this->getAbstractType() === 'enum') {
            $column['enumValues'] = $this->enumValues;
        }

        if ($this->getAbstractType() === 'decimal') {
            $column['precision'] = $this->precision;
            $column['scale'] = $this->scale;
        }

        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrecision(): int
    {
        return $this->precision;
    }

    /**
     * {@inheritdoc}
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDefaultValue(): bool
    {
        return $this->defaultValue !== null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws DefaultValueException
     */
    public function getDefaultValue()
    {
        if (!$this->hasDefaultValue()) {
            return null;
        }

        if ($this->defaultValue instanceof FragmentInterface) {
            //Defined as SQL piece
            return $this->defaultValue;
        }

        if (in_array($this->getAbstractType(), ['time', 'date', 'datetime', 'timestamp'])) {
            return $this->formatDatetime($this->getAbstractType(), $this->defaultValue);
        }

        switch ($this->getType()) {
            case 'int':
                return (int)$this->defaultValue;
            case 'float':
                return (float)$this->defaultValue;
            case 'bool':
                if (is_string($this->defaultValue) && strtolower($this->defaultValue) === 'false') {
                    return false;
                }

                return (bool)$this->defaultValue;
        }

        return (string)$this->defaultValue;
    }

    /**
     * Get every associated column constraint names.
     *
     * @return array
     */
    public function getConstraints(): array
    {
        return [];
    }

    /**
     * Get allowed enum values.
     *
     * @return array
     */
    public function getEnumValues(): array
    {
        return $this->enumValues;
    }

    /**
     * {@inheritdoc}
     */
    public function getInternalType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        $schemaType = $this->getAbstractType();
        foreach ($this->phpMapping as $phpType => $candidates) {
            if (in_array($schemaType, $candidates, true)) {
                return $phpType;
            }
        }

        return self::STRING;
    }

    /**
     * Returns type defined by the user, only until schema sync. Attention, this value is only preserved during the
     * declaration process. Value will become null after the schema fetched from database.
     *
     * @return string|null
     * @internal
     */
    public function getDeclaredType(): ?string
    {
        return $this->userType;
    }

    /**
     * DBMS specific reverse mapping must map database specific type into limited set of abstract
     * types.
     *
     * @return string
     */
    public function getAbstractType(): string
    {
        foreach ($this->reverseMapping as $type => $candidates) {
            foreach ($candidates as $candidate) {
                if (is_string($candidate)) {
                    if (strtolower($candidate) === strtolower($this->type)) {
                        return $type;
                    }

                    continue;
                }

                if (strtolower($candidate['type']) !== strtolower($this->type)) {
                    continue;
                }

                foreach ($candidate as $option => $required) {
                    if ($option === 'type') {
                        continue;
                    }

                    if ($this->{$option} !== $required) {
                        continue 2;
                    }
                }

                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * Give column new abstract type. DBMS specific implementation must map provided type into one
     * of internal database values.
     *
     * Attention, changing type of existed columns in some databases has a lot of restrictions like
     * cross type conversions and etc. Try do not change column type without a reason.
     *
     * @param string $abstract Abstract or virtual type declared in mapping.
     * @return self|$this
     *
     * @throws SchemaException
     * @todo Support native database types (simply bypass abstractType)!
     */
    public function type(string $abstract): AbstractColumn
    {
        if (isset($this->aliases[$abstract])) {
            //Make recursive
            $abstract = $this->aliases[$abstract];
        }

        if (!isset($this->mapping[$abstract])) {
            throw new SchemaException("Undefined abstract/virtual type '{$abstract}'");
        }

        // Originally specified type.
        $this->userType = $abstract;

        // Resetting all values to default state.
        $this->size = $this->precision = $this->scale = 0;
        $this->enumValues = [];

        // Abstract type points to DBMS specific type
        if (is_string($this->mapping[$abstract])) {
            $this->type = $this->mapping[$abstract];

            return $this;
        }

        // Configuring column properties based on abstractType preferences
        foreach ($this->mapping[$abstract] as $property => $value) {
            $this->{$property} = $value;
        }

        return $this;
    }

    /**
     * Set column nullable/not nullable.
     *
     * @param bool $nullable
     * @return self|$this
     */
    public function nullable(bool $nullable = true): AbstractColumn
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Change column default value (can be forbidden for some column types).
     * Use Database::TIMESTAMP_NOW to use driver specific NOW() function.
     *
     * @param mixed $value
     * @return self|$this
     */
    public function defaultValue($value): AbstractColumn
    {
        //Forcing driver specific values
        if ($value === self::DATETIME_NOW) {
            $value = static::DATETIME_NOW;
        }

        $this->defaultValue = $value;

        return $this;
    }

    /**
     * Set column as enum type and specify set of allowed values. Most of drivers will emulate enums
     * using column constraints.
     *
     * Examples:
     * $table->status->enum(['active', 'disabled']);
     * $table->status->enum('active', 'disabled');
     *
     * @param string|array $values Enum values (array or comma separated). String values only.
     * @return self
     */
    public function enum($values): AbstractColumn
    {
        $this->type('enum');
        $this->enumValues = array_map(
            'strval',
            is_array($values) ? $values : func_get_args()
        );

        return $this;
    }

    /**
     * Set column type as string with limited size. Maximum allowed size is 255 bytes, use "text"
     * abstract types for longer strings.
     *
     * Strings are perfect type to store email addresses as it big enough to store valid address
     * and
     * can be covered with unique index.
     *
     * @link http://stackoverflow.com/questions/386294/what-is-the-maximum-length-of-a-valid-email-address
     *
     * @param int $size Max string length.
     * @return self|$this
     *
     * @throws SchemaException
     */
    public function string(int $size = 255): AbstractColumn
    {
        $this->type('string');

        if ($size > 255) {
            throw new SchemaException(
                'String size can\'t exceed 255 characters. Use text instead'
            );
        }

        if ($size < 0) {
            throw new SchemaException(
                'Invalid string length value'
            );
        }

        $this->size = (int)$size;

        return $this;
    }

    /**
     * Set column type as decimal with specific precision and scale.
     *
     * @param int $precision
     * @param int $scale
     * @return self|$this
     *
     * @throws SchemaException
     */
    public function decimal(int $precision, int $scale = 0): AbstractColumn
    {
        $this->type('decimal');

        if (empty($precision)) {
            throw new SchemaException('Invalid precision value');
        }

        $this->precision = (int)$precision;
        $this->scale = (int)$scale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(DriverInterface $driver): string
    {
        $statement = [$driver->identifier($this->name), $this->type];

        if ($this->getAbstractType() === 'enum') {
            //Enum specific column options
            if (!empty($enumDefinition = $this->quoteEnum($driver))) {
                $statement[] = $enumDefinition;
            }
        } elseif (!empty($this->precision)) {
            $statement[] = "({$this->precision}, {$this->scale})";
        } elseif (!empty($this->size)) {
            $statement[] = "({$this->size})";
        }

        $statement[] = $this->nullable ? 'NULL' : 'NOT NULL';

        if ($this->defaultValue !== null) {
            $statement[] = "DEFAULT {$this->quoteDefault($driver)}";
        }

        return implode(' ', $statement);
    }

    /**
     * @param AbstractColumn $initial
     * @return bool
     */
    public function compare(AbstractColumn $initial): bool
    {
        $normalized = clone $initial;

        // soft compare, todo: improve
        if ($this == $normalized) {
            return true;
        }

        $columnVars = get_object_vars($this);
        $dbColumnVars = get_object_vars($normalized);

        $difference = [];
        foreach ($columnVars as $name => $value) {
            if (in_array($name, static::EXCLUDE_FROM_COMPARE, true)) {
                continue;
            }

            if ($name === 'defaultValue') {
                //Default values has to compared using type-casted value
                if ($this->getDefaultValue() != $initial->getDefaultValue()) {
                    $difference[] = $name;
                } elseif (
                    $this->getDefaultValue() !== $initial->getDefaultValue()
                    && (!is_object($this->getDefaultValue()) && !is_object($initial->getDefaultValue()))
                ) {
                    $difference[] = $name;
                }

                continue;
            }

            if ($value !== $dbColumnVars[$name]) {
                $difference[] = $name;
            }
        }

        return empty($difference);
    }

    /**
     * Get database specific enum type definition options.
     *
     * @param DriverInterface $driver
     * @return string
     */
    protected function quoteEnum(DriverInterface $driver): string
    {
        $enumValues = [];
        foreach ($this->enumValues as $value) {
            $enumValues[] = $driver->quote($value);
        }

        if (!empty($enumValues)) {
            return '(' . implode(', ', $enumValues) . ')';
        }

        return '';
    }

    /**
     * Must return driver specific default value.
     *
     * @param DriverInterface $driver
     * @return string
     */
    protected function quoteDefault(DriverInterface $driver): string
    {
        $defaultValue = $this->getDefaultValue();
        if ($defaultValue === null) {
            return 'NULL';
        }

        if ($defaultValue instanceof FragmentInterface) {
            return $driver->getQueryCompiler()->compile(
                new QueryParameters(),
                '',
                $defaultValue
            );
        }

        if ($this->getType() === 'bool') {
            return $defaultValue ? 'TRUE' : 'FALSE';
        }

        if ($this->getType() === 'float') {
            return sprintf('%F', $defaultValue);
        }

        if ($this->getType() === 'int') {
            return (string)$defaultValue;
        }

        return $driver->quote($defaultValue);
    }

    /**
     * Ensure that datetime fields are correctly formatted.
     *
     * @param string $type
     * @param string $value
     * @return string|FragmentInterface|\DateTime
     *
     * @throws DefaultValueException
     */
    protected function formatDatetime(string $type, $value)
    {
        if ($value === static::DATETIME_NOW) {
            //Dynamic default value
            return new Fragment($value);
        }

        if ($value instanceof \DateTimeInterface) {
            $datetime = clone $value;
        } else {
            if (is_numeric($value)) {
                //Presumably timestamp
                $datetime = new DateTimeImmutable('now', $this->timezone);
                $datetime = $datetime->setTimestamp($value);
            } else {
                $datetime = new DateTimeImmutable($value, $this->timezone);
            }
        }

        switch ($type) {
            case 'datetime':
            case 'timestamp':
                //Driver should handle conversion automatically in this case
                return $datetime;
            case 'time':
                return $datetime->format(static::TIME_FORMAT);
            case 'date':
                return $datetime->format(static::DATE_FORMAT);
        }

        return $value;
    }
}
