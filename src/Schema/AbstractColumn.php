<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema;

use DateTimeImmutable;
use Cycle\Database\ColumnInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\DefaultValueException;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Schema\Traits\ElementTrait;

/**
 * Abstract column schema with read (see ColumnInterface) and write abilities. Must be implemented
 * by driver to support DBMS specific syntax and creation rules.
 *
 * Shortcuts for various column types:
 *
 * @method $this|AbstractColumn primary()
 * @method $this|AbstractColumn bigPrimary()
 * @method $this|AbstractColumn boolean()
 * @method $this|AbstractColumn integer()
 * @method $this|AbstractColumn tinyInteger()
 * @method $this|AbstractColumn bigInteger()
 * @method $this|AbstractColumn text()
 * @method $this|AbstractColumn tinyText()
 * @method $this|AbstractColumn longText()
 * @method $this|AbstractColumn double()
 * @method $this|AbstractColumn float()
 * @method $this|AbstractColumn datetime()
 * @method $this|AbstractColumn date()
 * @method $this|AbstractColumn time()
 * @method $this|AbstractColumn timestamp()
 * @method $this|AbstractColumn binary()
 * @method $this|AbstractColumn tinyBinary()
 * @method $this|AbstractColumn longBinary()
 * @method $this|AbstractColumn json()
 * @method $this|AbstractColumn uuid()
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
     */
    protected array $mapping = [
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
     */
    protected array $reverseMapping = [
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
     */
    protected ?string $userType = null;

    /**
     * DBMS specific column type.
     */
    protected string $type = '';

    protected ?\DateTimeZone $timezone = null;

    /**
     * Indicates that column can contain null values.
     */
    protected bool $nullable = true;

    /**
     * Default column value, may not be applied to some datatypes (for example to primary keys),
     * should follow type size and other options.
     */
    protected mixed $defaultValue = null;

    /**
     * Column type size, can have different meanings for different datatypes.
     */
    protected int $size = 0;

    /**
     * Precision of column, applied only for "decimal" type.
     */
    protected int $precision = 0;

    /**
     * Scale of column, applied only for "decimal" type.
     */
    protected int $scale = 0;

    /**
     * List of allowed enum values.
     */
    protected array $enumValues = [];

    /**
     * Abstract type aliases (for consistency).
     */
    private array $aliases = [
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
     */
    private array $phpMapping = [
        self::INT   => ['primary', 'bigPrimary', 'integer', 'tinyInteger', 'bigInteger'],
        self::BOOL  => ['boolean'],
        self::FLOAT => ['double', 'float', 'decimal'],
    ];

    /**
     * @psalm-param non-empty-string $table
     * @psalm-param non-empty-string $name
     */
    public function __construct(
        protected string $table,
        protected string $name,
        \DateTimeZone $timezone = null
    ) {
        $this->timezone = $timezone ?? new \DateTimeZone(date_default_timezone_get());
    }

    /**
     * Shortcut for AbstractColumn->type() method.
     *
     * @psalm-param non-empty-string $type
     */
    public function __call(string $type, array $arguments = []): self
    {
        return $this->type($type);
    }

    public function __toString(): string
    {
        return $this->table . '.' . $this->getName();
    }

    /**
     * Simplified way to dump information.
     */
    public function __debugInfo(): array
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

    public function getSize(): int
    {
        return $this->size;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function hasDefaultValue(): bool
    {
        return $this->defaultValue !== null;
    }

    /**
     * @throws DefaultValueException
     */
    public function getDefaultValue(): mixed
    {
        if (!$this->hasDefaultValue()) {
            return null;
        }

        if ($this->defaultValue instanceof FragmentInterface) {
            //Defined as SQL piece
            return $this->defaultValue;
        }

        if (\in_array($this->getAbstractType(), ['time', 'date', 'datetime', 'timestamp'])) {
            return $this->formatDatetime($this->getAbstractType(), $this->defaultValue);
        }

        return match ($this->getType()) {
            'int' => (int) $this->defaultValue,
            'float' => (float) $this->defaultValue,
            'bool' => \is_string($this->defaultValue) && strtolower($this->defaultValue) === 'false'
                ? false : (bool) $this->defaultValue,
            default => (string)$this->defaultValue
        };
    }

    /**
     * Get every associated column constraint names.
     */
    public function getConstraints(): array
    {
        return [];
    }

    /**
     * Get allowed enum values.
     */
    public function getEnumValues(): array
    {
        return $this->enumValues;
    }

    public function getInternalType(): string
    {
        return $this->type;
    }

    /**
     * @psalm-return non-empty-string
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
     * @internal
     */
    public function getDeclaredType(): ?string
    {
        return $this->userType;
    }

    /**
     * DBMS specific reverse mapping must map database specific type into limited set of abstract
     * types.
     */
    public function getAbstractType(): string
    {
        foreach ($this->reverseMapping as $type => $candidates) {
            foreach ($candidates as $candidate) {
                if (\is_string($candidate)) {
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
     * @psalm-param non-empty-string $abstract Abstract or virtual type declared in mapping.
     *
     * @throws SchemaException
     *
     * @todo Support native database types (simply bypass abstractType)!
     */
    public function type(string $abstract): self
    {
        if (isset($this->aliases[$abstract])) {
            //Make recursive
            $abstract = $this->aliases[$abstract];
        }

        isset($this->mapping[$abstract]) or throw new SchemaException("Undefined abstract/virtual type '{$abstract}'");

        // Originally specified type.
        $this->userType = $abstract;

        // Resetting all values to default state.
        $this->size = $this->precision = $this->scale = 0;
        $this->enumValues = [];

        // Abstract type points to DBMS specific type
        if (\is_string($this->mapping[$abstract])) {
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
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Change column default value (can be forbidden for some column types).
     * Use Database::TIMESTAMP_NOW to use driver specific NOW() function.
     */
    public function defaultValue(mixed $value): self
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
     * @param array|string $values Enum values (array or comma separated). String values only.
     */
    public function enum(string|array $values): self
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
     *
     * @throws SchemaException
     */
    public function string(int $size = 255): self
    {
        $this->type('string');

        $size < 0 && throw new SchemaException('Invalid string length value');

        $this->size = $size;

        return $this;
    }

    /**
     * Set column type as decimal with specific precision and scale.
     *
     * @throws SchemaException
     */
    public function decimal(int $precision, int $scale = 0): self
    {
        $this->type('decimal');

        empty($precision) && throw new SchemaException('Invalid precision value');

        $this->precision = $precision;
        $this->scale = $scale;

        return $this;
    }

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

    public function compare(self $initial): bool
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
            if (\in_array($name, static::EXCLUDE_FROM_COMPARE, true)) {
                continue;
            }

            if ($name === 'defaultValue') {
                //Default values has to compared using type-casted value
                if ($this->getDefaultValue() != $initial->getDefaultValue()) {
                    $difference[] = $name;
                } elseif (
                    $this->getDefaultValue() !== $initial->getDefaultValue()
                    && (!\is_object($this->getDefaultValue()) && !\is_object($initial->getDefaultValue()))
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
     */
    protected function quoteEnum(DriverInterface $driver): string
    {
        $enumValues = [];
        foreach ($this->enumValues as $value) {
            $enumValues[] = $driver->quote($value);
        }

        return !empty($enumValues) ? '(' . implode(', ', $enumValues) . ')' : '';
    }

    /**
     * Must return driver specific default value.
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

        return match ($this->getType()) {
            'bool' => $defaultValue ? 'TRUE' : 'FALSE',
            'float' => sprintf('%F', $defaultValue),
            'int' => (string) $defaultValue,
            default => $driver->quote($defaultValue)
        };
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

        return match ($type) {
            'datetime', 'timestamp' => $datetime,
            'time' => $datetime->format(static::TIME_FORMAT),
            'date' => $datetime->format(static::DATE_FORMAT),
            default => $value
        };
    }
}
