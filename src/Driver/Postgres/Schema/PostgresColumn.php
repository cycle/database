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

class PostgresColumn extends AbstractColumn
{
    private const WITH_TIMEZONE = 'with time zone';
    private const WITHOUT_TIMEZONE = 'without time zone';

    /**
     * Default timestamp expression (driver specific).
     */
    public const DATETIME_NOW = 'now()';

    /**
     * Private state related values.
     */
    public const EXCLUDE_FROM_COMPARE = [
        'userType',
        'timezone',
        'constrained',
        'constrainName',
    ];

    protected array $mapping = [
        //Primary sequences
        'primary'     => ['type' => 'serial', 'autoIncrement' => true, 'nullable' => false],
        'bigPrimary'  => ['type' => 'bigserial', 'autoIncrement' => true, 'nullable' => false],

        //Enum type (mapped via method)
        'enum'        => 'enum',

        //Logical types
        'boolean'     => 'boolean',

        //Integer types (size can always be changed with size method), longInteger has method alias
        //bigInteger
        'integer'     => 'integer',
        'tinyInteger' => 'smallint',
        'smallInteger'=> 'smallint',
        'bigInteger'  => 'bigint',

        //String with specified length (mapped via method)
        'string'      => 'character varying',

        //Generic types
        'text'        => 'text',
        'tinyText'    => 'text',
        'longText'    => 'text',

        //Real types
        'double'      => 'double precision',
        'float'       => 'real',

        //Decimal type (mapped via method)
        'decimal'     => 'numeric',

        //Date and Time types
        'datetime'    => 'timestamp',
        'date'        => 'date',
        'time'        => 'time',
        'timestamp'   => 'timestamp',
        'timestamptz' => 'timestamp',

        //Binary types
        'binary'      => 'bytea',
        'tinyBinary'  => 'bytea',
        'longBinary'  => 'bytea',

        //Additional types
        'json'        => 'text',
        'jsonb'       => 'jsonb',
        'uuid'        => 'uuid',
    ];

    protected array $reverseMapping = [
        'primary'     => ['serial'],
        'bigPrimary'  => ['bigserial'],
        'enum'        => ['enum'],
        'boolean'     => ['boolean'],
        'integer'     => ['int', 'integer', 'int4'],
        'tinyInteger' => ['smallint'],
        'smallInteger'=> ['smallint'],
        'bigInteger'  => ['bigint', 'int8'],
        'string'      => ['character varying', 'character'],
        'text'        => ['text'],
        'double'      => ['double precision'],
        'float'       => ['real', 'money'],
        'decimal'     => ['numeric'],
        'date'        => ['date'],
        'time'        => ['time', 'time with time zone', 'time without time zone'],
        'timestamp'   => ['timestamp', 'timestamp without time zone'],
        'timestamptz' => ['timestamp with time zone'],
        'binary'      => ['bytea'],
        'json'        => ['json'],
        'jsonb'       => ['jsonb'],
    ];

    /**
     * Field is auto incremental.
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

    protected bool $withTimezone = false;

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
        $this->enumValues = array_map('strval', \is_array($values) ? $values : \func_get_args());

        $this->type = 'character varying';
        foreach ($this->enumValues as $value) {
            $this->size = max((int)$this->size, \strlen($value));
        }

        return $this;
    }

    /**
     * @psalm-return non-empty-string
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

        //We have add constraint for enum type
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
                    $enumSize = max($enumSize, strlen($value));
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
                . "CHECK ({$identifier} IN (" . implode(', ', $enumValues) . '))';
        }

        return $operations;
    }

    /**
     * @psalm-param non-empty-string $table Table name.
     *
     * @param DriverInterface $driver Postgres columns are bit more complex.
     */
    public static function createInstance(
        string $table,
        array $schema,
        DriverInterface $driver
    ): self {
        $column = new self($table, $schema['column_name'], $driver->getTimezone());

        $column->type = match (true) {
            $schema['typname'] === 'timestamp' => 'timestamp',
            $schema['typname'] === 'date' => 'date',
            $schema['typname'] === 'time' => 'time',
            default => $schema['data_type']
        };

        $column->defaultValue = $schema['column_default'];
        $column->nullable = $schema['is_nullable'] === 'YES';

        if (
            \is_string($column->defaultValue)
            && \in_array($column->type, ['int', 'bigint', 'integer'])
            && preg_match('/nextval(.*)/', $column->defaultValue)
        ) {
            $column->type = ($column->type === 'bigint' ? 'bigserial' : 'serial');
            $column->autoIncrement = true;

            $column->defaultValue = new Fragment($column->defaultValue);

            return $column;
        }

        if ($schema['character_maximum_length'] !== null && str_contains($column->type, 'char')) {
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

        if ($column->type === 'timestamp' || $column->type === 'time') {
            $column->size = (int) $schema['datetime_precision'];
        }

        if (!empty($column->size) && str_contains($column->type, 'char')) {
            //Potential enum with manually created constraint (check in)
            self::resolveConstrains($driver, $schema, $column);
        }

        $column->normalizeDefault();

        return $column;
    }

    public function compare(AbstractColumn $initial): bool
    {
        if (parent::compare($initial)) {
            return true;
        }

        return (bool) (
            in_array($this->getAbstractType(), ['primary', 'bigPrimary'])
            && $initial->getDefaultValue() != $this->getDefaultValue()
        )
            //PG adds default values to primary keys



         ;
    }

    public function timestamp(int $size = 0): self
    {
        $this->type('timestamp');

        ($size < 0 || $size > 6) && throw new SchemaException('Invalid timestamp length value');

        $this->size = $size;

        return $this;
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
     * Get/generate name for enum constraint.
     */
    private function enumConstraint(): string
    {
        if (empty($this->constrainName)) {
            $this->constrainName = str_replace('.', '_', $this->table) . '_' . $this->getName() . '_enum_' . uniqid();
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

        if (preg_match('/^\'?(.*?)\'?::(.+)/', $this->defaultValue, $matches)) {
            //In database: 'value'::TYPE
            $this->defaultValue = $matches[1];
        } elseif ($this->type === 'bit') {
            $this->defaultValue = bindec(
                substr($this->defaultValue, 2, strpos($this->defaultValue, '::') - 3)
            );
        } elseif ($this->type === 'boolean') {
            $this->defaultValue = (strtolower($this->defaultValue) === 'true');
        }

        $type = $this->getType();
        if ($type === self::FLOAT || $type === self::INT) {
            if (preg_match('/^\(?(.*?)\)?(?!::(.+))?$/', $this->defaultValue, $matches)) {
                //Negative numeric values
                $this->defaultValue = $matches[1];
            }
        }
    }

    /**
     * Resolving enum constrain and converting it into proper enum values set.
     */
    private static function resolveConstrains(
        DriverInterface $driver,
        array $schema,
        self $column
    ): void {
        $query = "SELECT conname, pg_get_constraintdef(oid) as consrc FROM pg_constraint
        WHERE conrelid = ? AND contype = 'c' AND conkey = ?";

        $constraints = $driver->query(
            $query,
            [
                $schema['tableOID'],
                '{' . $schema['dtd_identifier'] . '}',
            ]
        );

        foreach ($constraints as $constraint) {
            if (preg_match('/ARRAY\[([^\]]+)\]/', $constraint['consrc'], $matches)) {
                $enumValues = explode(',', $matches[1]);
                foreach ($enumValues as &$value) {
                    if (preg_match("/^'?(.*?)'?::(.+)/", trim($value, ' ()'), $matches)) {
                        //In database: 'value'::TYPE
                        $value = $matches[1];
                    }

                    unset($value);
                }
                unset($value);

                $column->enumValues = $enumValues;
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

        $column->enumValues = explode(',', substr($range, 1, -1));

        if (!empty($column->defaultValue)) {
            //In database: 'value'::enumType
            $column->defaultValue = substr(
                $column->defaultValue,
                1,
                strpos($column->defaultValue, $column->type) - 4
            );
        }
    }
}
