<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema;

use Cycle\Database\Driver\Jsoner;
use Cycle\Database\Schema\Attribute\ColumnAttribute;
use Cycle\Database\Schema\Traits\ColumnAttributesTrait;
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
 * @method $this|AbstractColumn smallPrimary()
 * @method $this|AbstractColumn bigPrimary()
 * @method $this|AbstractColumn boolean()
 * @method $this|AbstractColumn integer()
 * @method $this|AbstractColumn tinyInteger()
 * @method $this|AbstractColumn smallInteger()
 * @method $this|AbstractColumn bigInteger()
 * @method $this|AbstractColumn text()
 * @method $this|AbstractColumn tinyText()
 * @method $this|AbstractColumn mediumText()
 * @method $this|AbstractColumn longText()
 * @method $this|AbstractColumn double()
 * @method $this|AbstractColumn float()
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
    use ColumnAttributesTrait;
    use ElementTrait;

    /**
     * Default timestamp expression (driver specific).
     */
    public const DATETIME_NOW = 'CURRENT_TIMESTAMP';

    /**
     * Value to be excluded from comparison.
     */
    public const EXCLUDE_FROM_COMPARE = ['timezone', 'userType', 'attributes'];

    /**
     * Normalization for time and dates.
     */
    public const DATE_FORMAT = 'Y-m-d';

    public const TIME_FORMAT = 'H:i:s';
    public const DATETIME_PRECISION = 6;

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
        'smallPrimary'  => null,
        'bigPrimary'  => null,

        //Enum type (mapped via method)
        'enum'        => null,

        //Logical types
        'boolean'     => null,

        //Integer types (size can always be changed with size method), longInteger has method alias
        //bigInteger
        'integer'     => null,
        'tinyInteger' => null,
        'smallInteger' => null,
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
     * Reverse mapping is responsible for generating abstract type based on database type and it's
     * options. Multiple database types can be mapped into one abstract type.
     *
     * @internal
     */
    protected array $reverseMapping = [
        'primary'     => [],
        'smallPrimary'  => [],
        'bigPrimary'  => [],
        'enum'        => [],
        'boolean'     => [],
        'integer'     => [],
        'tinyInteger' => [],
        'smallInteger' => [],
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
    #[ColumnAttribute]
    protected bool $nullable = true;

    /**
     * Default column value, may not be applied to some datatypes (for example to primary keys),
     * should follow type size and other options.
     */
    #[ColumnAttribute]
    protected mixed $defaultValue = null;

    /**
     * Column type size, can have different meanings for different datatypes.
     */
    #[ColumnAttribute]
    protected int $size = 0;

    /**
     * Precision of column, applied only for "decimal" type.
     */
    #[ColumnAttribute(['decimal'])]
    protected int $precision = 0;

    /**
     * Scale of column, applied only for "decimal" type.
     */
    #[ColumnAttribute(['decimal'])]
    protected int $scale = 0;

    /**
     * List of allowed enum values.
     */
    protected array $enumValues = [];

    /**
     * Abstract type aliases (for consistency).
     */
    protected array $aliases = [
        'int'            => 'integer',
        'smallint'       => 'smallInteger',
        'bigint'         => 'bigInteger',
        'incremental'    => 'primary',
        'smallIncremental' => 'smallPrimary',
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
        self::INT   => ['primary', 'smallPrimary', 'bigPrimary', 'integer', 'tinyInteger', 'smallInteger', 'bigInteger'],
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
        ?\DateTimeZone $timezone = null,
    ) {
        $this->timezone = $timezone ?? new \DateTimeZone(\date_default_timezone_get());
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
            'bool' => \is_string($this->defaultValue) && \strtolower($this->defaultValue) === 'false'
                ? false : (bool) $this->defaultValue,
            default => (string) $this->defaultValue,
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
            if (\in_array($schemaType, $candidates, true)) {
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
                    if (\strtolower($candidate) === \strtolower($this->type)) {
                        return $type;
                    }

                    continue;
                }

                if (\strtolower($candidate['type']) !== \strtolower($this->type)) {
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
     * @todo Support native database types (simply bypass abstractType)!
     */
    public function type(string $abstract): self
    {
        if (isset($this->aliases[$abstract])) {
            //Make recursive
            $abstract = $this->aliases[$abstract];
        }

        if (!isset($this->mapping[$abstract])) {
            $this->type = $abstract;
            $this->userType = $abstract;

            return $this;
        }

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
     * Use {@see AbstractColumn::DATETIME_NOW} to use driver specific NOW() function.
     * Column with JSON type can be set to default value of array type.
     */
    public function defaultValue(mixed $value): self
    {
        $this->defaultValue = match (true) {
            $value === self::DATETIME_NOW => static::DATETIME_NOW,
            static::isJson($this) !== false && \is_array($value) => Jsoner::toJson($value),
            default => $value,
        };

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
        $this->enumValues = \array_map(
            'strval',
            \is_array($values) ? $values : \func_get_args(),
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

    public function datetime(int $size = 0, mixed ...$attributes): self
    {
        $this->type('datetime');
        $this->fillAttributes($attributes);

        ($size < 0 || $size > static::DATETIME_PRECISION) && throw new SchemaException(
            \sprintf('Invalid %s precision value.', $this->getAbstractType()),
        );
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

        if (static::isEnum($this)) {
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

        return \implode(' ', $statement);
    }

    public function compare(self $initial): bool
    {
        $normalized = clone $initial;

        // soft compare, todo: improve
        if ($this == $normalized) {
            return true;
        }

        $columnVars = \get_object_vars($this);
        $dbColumnVars = \get_object_vars($normalized);

        $difference = [];
        foreach ($columnVars as $name => $value) {
            if (\in_array($name, static::EXCLUDE_FROM_COMPARE, true)) {
                continue;
            }

            if ($name === 'type') {
                // user defined type
                if (!isset($this->mapping[$this->type]) && $this->type === $this->userType) {
                    continue;
                }
            }

            if ($name === 'defaultValue') {
                $defaultValue = $this->getDefaultValue() instanceof FragmentInterface
                    ? $this->getDefaultValue()->__toString()
                    : $this->getDefaultValue();
                $initialDefaultValue = $initial->getDefaultValue() instanceof FragmentInterface
                    ? $initial->getDefaultValue()->__toString()
                    : $initial->getDefaultValue();

                //Default values has to compared using type-casted value
                if ($defaultValue != $initialDefaultValue) {
                    $difference[] = $name;
                } elseif (
                    $defaultValue !== $initialDefaultValue
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

    public function isReadonlySchema(): bool
    {
        return $this->getAttributes()['readonlySchema'] ?? false;
    }

    /**
     * Shortcut for AbstractColumn->type() method.
     *
     * @psalm-param non-empty-string $name
     */
    public function __call(string $name, array $arguments = []): self
    {
        if (isset($this->aliases[$name]) || isset($this->mapping[$name])) {
            $this->type($name);
        }

        // The type must be set before the attributes are filled.
        !empty($this->type) or throw new SchemaException('Undefined abstract/virtual type');

        if (\count($arguments) === 1 && \key($arguments) === 0) {
            if (\array_key_exists($name, $this->getAttributesMap())) {
                $this->fillAttributes([$name => $arguments[0]]);
                return $this;
            }
        }

        $this->fillAttributes($arguments);

        return $this;
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

        if (static::isEnum($this)) {
            $column['enumValues'] = $this->enumValues;
        }

        if ($this->getAbstractType() === 'decimal') {
            $column['precision'] = $this->precision;
            $column['scale'] = $this->scale;
        }

        if ($this->attributes !== []) {
            $column['attributes'] = $this->attributes;
        }

        return $column;
    }

    protected static function isEnum(self $column): bool
    {
        return $column->getAbstractType() === 'enum';
    }

    /**
     * Checks if the column is JSON or no.
     *
     * Returns null if it's impossible to explicitly define the JSON type.
     */
    protected static function isJson(self $column): ?bool
    {
        return $column->getAbstractType() === 'json';
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

        return !empty($enumValues) ? '(' . \implode(', ', $enumValues) . ')' : '';
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
                $defaultValue,
            );
        }

        return match ($this->getType()) {
            'bool' => $defaultValue ? 'TRUE' : 'FALSE',
            'float' => \sprintf('%F', $defaultValue),
            'int' => (string) $defaultValue,
            default => $driver->quote($defaultValue),
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
        string|int|\DateTimeInterface $value,
    ): \DateTimeInterface|FragmentInterface|string {
        if ($value === static::DATETIME_NOW) {
            //Dynamic default value
            return new Fragment($value);
        }

        if ($value instanceof \DateTimeInterface) {
            $datetime = clone $value;
        } else {
            if (\is_numeric($value)) {
                //Presumably timestamp
                $datetime = new \DateTimeImmutable('now', $this->timezone);
                $datetime = $datetime->setTimestamp($value);
            } else {
                $datetime = new \DateTimeImmutable($value, $this->timezone);
            }
        }

        return match ($type) {
            'datetime', 'timestamp' => $datetime,
            'time' => $datetime->format(static::TIME_FORMAT),
            'date' => $datetime->format(static::DATE_FORMAT),
            default => $value,
        };
    }
}
