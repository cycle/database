<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\Attribute\ColumnAttribute;

/**
 * @method $this timestamptz(int $size = 0)
 * @method $this timetz()
 * @method $this bitVarying(int $size = 0)
 * @method $this bit(int $size = 1)
 * @method $this int4range()
 * @method $this int8range()
 * @method $this numrange()
 * @method $this tsrange()
 * @method $this tstzrange()
 * @method $this daterange()
 * @method $this point()
 * @method $this line()
 * @method $this lseg()
 * @method $this box()
 * @method $this path()
 * @method $this polygon()
 * @method $this circle()
 * @method $this cidr()
 * @method $this inet()
 * @method $this macaddr()
 * @method $this macaddr8()
 * @method $this tsvector()
 * @method $this tsquery()
 * @method $this smallSerial()
 * @method $this serial()
 * @method $this bigSerial()
 */
class PostgresColumn extends AbstractColumn
{
    private const WITH_TIMEZONE = 'with time zone';
    private const WITHOUT_TIMEZONE = 'without time zone';
    private const SERIAL_TYPES = [
        'smallPrimary',
        'primary',
        'bigPrimary',
        'smallserial',
        'serial',
        'bigserial',
    ];
    protected const INTEGER_TYPES = ['int', 'bigint', 'integer', 'smallint'];

    /**
     * Default timestamp expression (driver specific).
     */
    public const DATETIME_NOW = 'now()';

    public const DATETIME_PRECISION = 6;

    /**
     * Private state related values.
     */
    public const EXCLUDE_FROM_COMPARE = [
        'userType',
        'timezone',
        'constrained',
        'constrainName',
        'attributes',
    ];

    protected const INTERVAL_TYPES = [
        'YEAR',
        'MONTH',
        'DAY',
        'HOUR',
        'MINUTE',
        'SECOND',
        'YEAR TO MONTH',
        'DAY TO HOUR',
        'DAY TO MINUTE',
        'DAY TO SECOND',
        'HOUR TO MINUTE',
        'HOUR TO SECOND',
        'MINUTE TO SECOND',
    ];
    protected const INTERVALS_WITH_ALLOWED_PRECISION = [
        'SECOND',
        'DAY TO SECOND',
        'HOUR TO SECOND',
        'MINUTE TO SECOND',
    ];

    protected array $aliases = [
        'int'            => 'integer',
        'smallint'       => 'smallInteger',
        'bigint'         => 'bigInteger',
        'incremental'    => 'primary',
        'bigIncremental' => 'bigPrimary',
        'bool'           => 'boolean',
        'blob'           => 'binary',
        'bitVarying'     => 'bit varying',
        'smallSerial'    => 'smallserial',
        'bigSerial'      => 'bigserial',
    ];
    protected array $mapping = [
        //Primary sequences
        'smallPrimary' => ['type' => 'smallserial', 'nullable' => false, 'isPrimary' => true],
        'primary'      => ['type' => 'serial', 'nullable' => false, 'isPrimary' => true],
        'bigPrimary'   => ['type' => 'bigserial', 'nullable' => false, 'isPrimary' => true],

        //Serial
        'smallserial' => ['type' => 'smallserial', 'nullable' => false],
        'serial'      => ['type' => 'serial', 'nullable' => false],
        'bigserial'   => ['type' => 'bigserial', 'nullable' => false],

        //Enum type (mapped via method)
        'enum'         => 'enum',

        //Logical types
        'boolean'      => 'boolean',

        //Integer types (size can always be changed with size method), longInteger has method alias
        //bigInteger
        'integer'      => 'integer',
        'tinyInteger'  => 'smallint',
        'smallInteger' => 'smallint',
        'bigInteger'   => 'bigint',

        //String with specified length (mapped via method)
        'string'       => ['type' => 'character varying', 'size' => 255],

        //Generic types
        'text'         => 'text',
        'mediumText'   => 'text',
        'tinyText'     => 'text',
        'longText'     => 'text',

        //Real types
        'double'       => 'double precision',
        'float'        => 'real',

        //Decimal type (mapped via method)
        'decimal'      => 'numeric',

        //Date and Time types
        'datetime'     => 'timestamp',
        'date'         => 'date',
        'time'         => 'time',
        'timetz'       => ['type' => 'time', 'withTimezone' => true],
        'timestamp'    => 'timestamp',
        'timestamptz'  => ['type' => 'timestamp', 'withTimezone' => true],
        'interval'     => 'interval',

        //Binary types
        'binary'       => 'bytea',
        'tinyBinary'   => 'bytea',
        'longBinary'   => 'bytea',

        //Bit-string
        'bit'          => ['type' => 'bit', 'size' => 1],
        'bit varying'  => 'bit varying',

        //Ranges
        'int4range'    => 'int4range',
        'int8range'    => 'int8range',
        'numrange'     => 'numrange',
        'tsrange'      => 'tsrange',
        'tstzrange'    => ['type' => 'tstzrange', 'withTimezone' => true],
        'daterange'    => 'daterange',

        //Additional types
        'json'         => 'json',
        'jsonb'        => 'jsonb',
        'uuid'         => 'uuid',
        'point'        => 'point',
        'line'         => 'line',
        'lseg'         => 'lseg',
        'box'          => 'box',
        'path'         => 'path',
        'polygon'      => 'polygon',
        'circle'       => 'circle',
        'cidr'         => 'cidr',
        'inet'         => 'inet',
        'macaddr'      => 'macaddr',
        'macaddr8'     => 'macaddr8',
        'tsvector'     => 'tsvector',
        'tsquery'      => 'tsquery',
    ];
    protected array $reverseMapping = [
        'smallPrimary' => [['type' => 'smallserial', 'isPrimary' => true]],
        'primary'      => [['type' => 'serial', 'isPrimary' => true]],
        'bigPrimary'   => [['type' => 'bigserial', 'isPrimary' => true]],
        'smallserial'  => [['type' => 'smallserial', 'isPrimary' => false]],
        'serial'       => [['type' => 'serial', 'isPrimary' => false]],
        'bigserial'    => [['type' => 'bigserial', 'isPrimary' => false]],
        'enum'         => ['enum'],
        'boolean'      => ['boolean'],
        'integer'      => ['int', 'integer', 'int4', 'int4range'],
        'tinyInteger'  => ['smallint'],
        'smallInteger' => ['smallint'],
        'bigInteger'   => ['bigint', 'int8', 'int8range'],
        'string'       => [
            'character varying',
            'character',
            'char',
            'point',
            'line',
            'lseg',
            'box',
            'path',
            'polygon',
            'circle',
            'cidr',
            'inet',
            'macaddr',
            'macaddr8',
            'tsvector',
            'tsquery',
        ],
        'text'         => ['text'],
        'double'       => ['double precision'],
        'float'        => ['real', 'money'],
        'decimal'      => ['numeric', 'numrange'],
        'date'         => ['date', 'daterange'],
        'time'         => [['type' => 'time', 'withTimezone' => false]],
        'timetz'       => [['type' => 'time', 'withTimezone' => true]],
        'timestamp'    => [
            ['type' => 'timestamp', 'withTimezone' => false],
            ['type' => 'tsrange', 'withTimezone' => false],
        ],
        'timestamptz'  => [
            ['type' => 'timestamp', 'withTimezone' => true],
            ['type' => 'tstzrange', 'withTimezone' => true],
        ],
        'binary'       => ['bytea'],
        'json'         => ['json'],
        'jsonb'        => ['jsonb'],
        'interval'     => ['interval'],
        'bit'          => ['bit', 'bit varying'],
    ];

    #[ColumnAttribute([
        'character varying',
        'bit',
        'bit varying',
        'datetime',
        'time',
        'timetz',
        'timestamp',
        'timestamptz',
    ])]
    protected int $size = 0;

    /**
     * Field is auto incremental.
     *
     * @deprecated since v2.5.0
     */
    protected bool $autoIncrement = false;

    /**
     * Indication that column has enum constrain.
     */
    protected bool $constrained = false;

    /**
     * Name of enum constraint associated with field.
     */
    protected string $constrainName = '';

    #[ColumnAttribute(['timestamp', 'time', 'timestamptz', 'timetz', 'tsrange', 'tstzrange'])]
    protected bool $withTimezone = false;

    #[ColumnAttribute(['interval'])]
    protected ?string $intervalType = null;

    #[ColumnAttribute(['numeric'])]
    protected int $precision = 0;

    #[ColumnAttribute(['numeric'])]
    protected int $scale = 0;

    /**
     * Internal field to determine if the serial is PK.
     */
    protected bool $isPrimary = false;

    /**
     * @psalm-param non-empty-string $table Table name.
     *
     * @param DriverInterface $driver Postgres columns are bit more complex.
     */
    public static function createInstance(
        string $table,
        array $schema,
        DriverInterface $driver,
    ): self {
        $column = new self($table, $schema['column_name'], $driver->getTimezone());

        $column->type = match (true) {
            $schema['typname'] === 'timestamp' || $schema['typname'] === 'timestamptz' => 'timestamp',
            $schema['typname'] === 'date' => 'date',
            $schema['typname'] === 'time' || $schema['typname'] === 'timetz' => 'time',
            \in_array($schema['typname'], ['_varchar', '_text', '_bpchar'], true) => 'string[]',
            \str_starts_with($schema['typname'], '_int') => 'integer[]',
            $schema['typname'] === '_numeric', \str_starts_with($schema['typname'], '_float') => 'float[]',
            default => $schema['data_type'],
        };

        $column->defaultValue = $schema['column_default'];
        $column->nullable = $schema['is_nullable'] === 'YES';

        if (
            \is_string($column->defaultValue)
            && \in_array($column->type, self::INTEGER_TYPES)
            && \preg_match('/nextval(.*)/', $column->defaultValue)
        ) {
            $column->type = match (true) {
                $column->type === 'bigint' => 'bigserial',
                $column->type === 'smallint' => 'smallserial',
                default => 'serial',
            };
            $column->autoIncrement = true;

            $column->defaultValue = new Fragment($column->defaultValue);

            if ($schema['is_primary']) {
                $column->isPrimary = true;
            }

            return $column;
        }

        if ($schema['character_maximum_length'] !== null && \str_contains($column->type, 'char')) {
            $column->size = (int) $schema['character_maximum_length'];
        }

        if ($column->type === 'numeric') {
            $column->precision = (int) $schema['numeric_precision'];
            $column->scale = (int) $schema['numeric_scale'];
        }

        if ($column->type === 'USER-DEFINED' && $schema['typtype'] === 'e') {
            $column->type = $schema['typname'];

            /**
             * Attention, this is not default enum type emulated via CHECK.
             * This is real Postgres enum type.
             */
            self::resolveEnum($driver, $column);
        }

        if ($column->type === 'timestamp' || $column->type === 'time' || $column->type === 'interval') {
            $column->size = (int) $schema['datetime_precision'];
        }

        if (
            $schema['typname'] === 'timestamptz' ||
            $schema['typname'] === 'timetz' ||
            $schema['typname'] === 'tstzrange'
        ) {
            $column->withTimezone = true;
        }

        if (!empty($column->size) && \str_contains($column->type, 'char')) {
            //Potential enum with manually created constraint (check in)
            self::resolveConstrains($driver, $schema, $column);
        }

        if ($column->type === 'interval' && \is_string($schema['interval_type'])) {
            $column->intervalType = \str_replace(\sprintf('(%s)', $column->size), '', $schema['interval_type']);
            if (!\in_array($column->intervalType, self::INTERVALS_WITH_ALLOWED_PRECISION, true)) {
                $column->size = 0;
            }
        }

        if (
            ($column->type === 'bit' || $column->type === 'bit varying') &&
            isset($schema['character_maximum_length'])
        ) {
            $column->size = (int) $schema['character_maximum_length'];
        }

        $column->normalizeDefault();

        return $column;
    }

    public function getConstraints(): array
    {
        $constraints = parent::getConstraints();

        if ($this->constrained) {
            $constraints[] = $this->constrainName;
        }

        return $constraints;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getAbstractType(): string
    {
        return !empty($this->enumValues) ? 'enum' : parent::getAbstractType();
    }

    public function smallPrimary(): AbstractColumn
    {
        if (!empty($this->type) && $this->type !== 'smallserial') {
            //Change type of already existed column (we can't use "serial" alias here)
            $this->type = 'smallint';

            return $this;
        }

        return $this->type('smallPrimary');
    }

    public function primary(): AbstractColumn
    {
        if (!empty($this->type) && $this->type !== 'serial') {
            //Change type of already existed column (we can't use "serial" alias here)
            $this->type = 'integer';

            return $this;
        }

        return $this->type('primary');
    }

    public function bigPrimary(): AbstractColumn
    {
        if (!empty($this->type) && $this->type !== 'bigserial') {
            //Change type of already existed column (we can't use "serial" alias here)
            $this->type = 'bigint';

            return $this;
        }

        return $this->type('bigPrimary');
    }

    public function enum(string|array $values): AbstractColumn
    {
        $this->enumValues = \array_map('strval', \is_array($values) ? $values : \func_get_args());

        $this->type = 'character varying';
        foreach ($this->enumValues as $value) {
            $this->size = \max((int) $this->size, \strlen($value));
        }

        return $this;
    }

    public function interval(int $size = 6, ?string $intervalType = null): AbstractColumn
    {
        if ($intervalType !== null && !\in_array($intervalType, self::INTERVALS_WITH_ALLOWED_PRECISION, true)) {
            $size = 0;
        }

        $this->type = 'interval';
        $this->size = $size;
        $this->intervalType = $intervalType;

        return $this;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function sqlStatement(DriverInterface $driver): string
    {
        $statement = [$driver->identifier($this->name), $this->type];

        if ($this->intervalType !== null && $this->getAbstractType() === 'interval') {
            if (!\in_array($this->intervalType, self::INTERVAL_TYPES, true)) {
                throw new SchemaException(\sprintf(
                    'Invalid interval type value. Valid values for interval type: `%s`.',
                    \implode('`, `', self::INTERVAL_TYPES),
                ));
            }
            $statement[] = $this->intervalType;
        }

        if ($this->getAbstractType() === 'enum') {
            //Enum specific column options
            if (!empty($enumDefinition = $this->quoteEnum($driver))) {
                $statement[] = $enumDefinition;
            }
        } elseif (!empty($this->precision)) {
            $statement[] = "({$this->precision}, {$this->scale})";
        } elseif (!empty($this->size) || $this->type === 'timestamp' || $this->type === 'time') {
            $statement[] = "({$this->size})";
        }

        if ($this->type === 'timestamp' || $this->type === 'time') {
            $statement[] = $this->withTimezone ? self::WITH_TIMEZONE : self::WITHOUT_TIMEZONE;
        }

        $statement[] = $this->nullable ? 'NULL' : 'NOT NULL';

        if ($this->defaultValue !== null) {
            $statement[] = "DEFAULT {$this->quoteDefault($driver)}";
        }

        $statement = \implode(' ', $statement);

        //We have to add constraint for enum type
        if ($this->getAbstractType() === 'enum') {
            $enumValues = [];
            foreach ($this->enumValues as $value) {
                $enumValues[] = $driver->quote($value);
            }

            $constrain = $driver->identifier($this->enumConstraint());
            $column = $driver->identifier($this->getName());
            $values = \implode(', ', $enumValues);

            return "{$statement} CONSTRAINT {$constrain} CHECK ($column IN ({$values}))";
        }

        //Nothing special
        return $statement;
    }

    /**
     * Generate set of operations need to change column.
     */
    public function alterOperations(DriverInterface $driver, AbstractColumn $initial): array
    {
        $operations = [];

        //To simplify comparation
        $currentType = [$this->type, $this->size, $this->precision, $this->scale];
        $initialType = [$initial->type, $initial->size, $initial->precision, $initial->scale];

        $identifier = $driver->identifier($this->getName());

        /*
         * This block defines column type and all variations.
         */
        if ($currentType !== $initialType) {
            if ($this->getAbstractType() === 'enum') {
                //Getting longest value
                $enumSize = $this->size;
                foreach ($this->enumValues as $value) {
                    $enumSize = \max($enumSize, \strlen($value));
                }

                $operations[] = "ALTER COLUMN {$identifier} TYPE character varying($enumSize)";
            } else {
                $type = "ALTER COLUMN {$identifier} TYPE {$this->type}";

                if (!empty($this->size)) {
                    $type .= "($this->size)";
                } elseif (!empty($this->precision)) {
                    $type .= "($this->precision, $this->scale)";
                }

                //Required to perform cross conversion
                $operations[] = "{$type} USING {$identifier}::{$this->type}";
            }
        }

        //Dropping enum constrain before any operation
        if ($this->constrained && $initial->getAbstractType() === 'enum') {
            $operations[] = 'DROP CONSTRAINT ' . $driver->identifier($this->enumConstraint());
        }

        //Default value set and dropping
        if ($initial->defaultValue !== $this->defaultValue) {
            if ($this->defaultValue === null) {
                $operations[] = "ALTER COLUMN {$identifier} DROP DEFAULT";
            } else {
                $operations[] = "ALTER COLUMN {$identifier} SET DEFAULT {$this->quoteDefault($driver)}";
            }
        }

        //Nullable option
        if ($initial->nullable !== $this->nullable) {
            $operations[] = "ALTER COLUMN {$identifier} " . (!$this->nullable ? 'SET' : 'DROP') . ' NOT NULL';
        }

        if ($this->getAbstractType() === 'enum') {
            $enumValues = [];
            foreach ($this->enumValues as $value) {
                $enumValues[] = $driver->quote($value);
            }

            $operations[] = "ADD CONSTRAINT {$driver->identifier($this->enumConstraint())} "
                . "CHECK ({$identifier} IN (" . \implode(', ', $enumValues) . '))';
        }

        return $operations;
    }

    public function compare(AbstractColumn $initial): bool
    {
        if (parent::compare($initial)) {
            return true;
        }

        return (bool) (
            \in_array($this->getAbstractType(), self::SERIAL_TYPES, true)
            && $initial->getDefaultValue() != $this->getDefaultValue()
        );
    }

    protected static function isJson(AbstractColumn $column): bool
    {
        return $column->getAbstractType() === 'json' || $column->getAbstractType() === 'jsonb';
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function quoteEnum(DriverInterface $driver): string
    {
        //Postgres enums are just constrained strings
        return '(' . $this->size . ')';
    }

    /**
     * Resolving enum constrain and converting it into proper enum values set.
     */
    private static function resolveConstrains(
        DriverInterface $driver,
        array $schema,
        self $column,
    ): void {
        $query = "SELECT conname, pg_get_constraintdef(oid) as consrc FROM pg_constraint
        WHERE conrelid = ? AND contype = 'c' AND conkey = ?";

        $constraints = $driver->query(
            $query,
            [
                $schema['tableOID'],
                '{' . $schema['dtd_identifier'] . '}',
            ],
        );

        foreach ($constraints as $constraint) {
            $values = static::parseEnumValues($constraint['consrc']);

            if ($values !== []) {
                $column->enumValues = $values;
                $column->constrainName = $constraint['conname'];
                $column->constrained = true;
            }
        }
    }

    /**
     * Resolve native ENUM type if presented.
     */
    private static function resolveEnum(DriverInterface $driver, self $column): void
    {
        $range = $driver->query('SELECT enum_range(NULL::' . $column->type . ')')->fetchColumn(0);

        $column->enumValues = \explode(',', \substr($range, 1, -1));

        if (!empty($column->defaultValue)) {
            //In database: 'value'::enumType
            $column->defaultValue = \substr(
                $column->defaultValue,
                1,
                \strpos($column->defaultValue, $column->type) - 4,
            );
        }
    }

    private static function parseEnumValues(string $constraint): array
    {
        if (\preg_match('/ARRAY\[([^\]]+)\]/', $constraint, $matches)) {
            $enumValues = \explode(',', $matches[1]);
            foreach ($enumValues as &$value) {
                if (\preg_match("/^'?([a-zA-Z0-9_]++)'?::([a-zA-Z0-9_]++)/", \trim($value, ' ()'), $matches)) {
                    //In database: 'value'::TYPE
                    $value = $matches[1];
                }

                unset($value);
            }
            unset($value);

            return $enumValues;
        }

        $pattern = '/CHECK \\(\\(\\([a-zA-Z0-9_]++\\)::([a-z]++) = \'([a-zA-Z0-9_]++)\'::([a-z]++)\\)\\)/i';
        if (\preg_match($pattern, $constraint, $matches) && !empty($matches[2])) {
            return [$matches[2]];
        }

        return [];
    }

    /**
     * Get/generate name for enum constraint.
     */
    private function enumConstraint(): string
    {
        if (empty($this->constrainName)) {
            $this->constrainName = \str_replace('.', '_', $this->table) . '_' . $this->getName() . '_enum_' . \uniqid();
        }

        return $this->constrainName;
    }

    /**
     * Normalize default value.
     */
    private function normalizeDefault(): void
    {
        if (!$this->hasDefaultValue()) {
            return;
        }

        if (\preg_match('/^\'?(.*?)\'?::(.+)/', $this->defaultValue, $matches)) {
            //In database: 'value'::TYPE
            $this->defaultValue = $matches[1];
        } elseif ($this->type === 'bit') {
            $this->defaultValue = \bindec(
                \substr($this->defaultValue, 2, \strpos($this->defaultValue, '::') - 3),
            );
        } elseif ($this->type === 'boolean') {
            $this->defaultValue = (\strtolower($this->defaultValue) === 'true');
        }

        $type = $this->getType();
        if ($type === self::FLOAT || $type === self::INT) {
            if (\preg_match('/^\(?(.*?)\)?(?!::(.+))?$/', $this->defaultValue, $matches)) {
                //Negative numeric values
                $this->defaultValue = $matches[1];
            }
        }
    }
}
