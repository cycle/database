<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\Postgres\Schema;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Injection\Fragment;
use Spiral\Database\Schema\AbstractColumn;

class PostgresColumn extends AbstractColumn
{
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
        'constrainName'
    ];

    /**
     * {@inheritdoc}
     */
    protected $mapping = [
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
        'datetime'    => 'timestamp without time zone',
        'date'        => 'date',
        'time'        => 'time without time zone',
        'timestamp'   => 'timestamp without time zone',

        //Binary types
        'binary'      => 'bytea',
        'tinyBinary'  => 'bytea',
        'longBinary'  => 'bytea',

        //Additional types
        'json'        => 'json',
        'jsonb'       => 'jsonb',
        'uuid'        => 'uuid'
    ];

    /**
     * {@inheritdoc}
     */
    protected $reverseMapping = [
        'primary'     => ['serial'],
        'bigPrimary'  => ['bigserial'],
        'enum'        => ['enum'],
        'boolean'     => ['boolean'],
        'integer'     => ['int', 'integer', 'int4'],
        'tinyInteger' => ['smallint'],
        'bigInteger'  => ['bigint', 'int8'],
        'string'      => ['character varying', 'character'],
        'text'        => ['text'],
        'double'      => ['double precision'],
        'float'       => ['real', 'money'],
        'decimal'     => ['numeric'],
        'date'        => ['date'],
        'time'        => ['time', 'time with time zone', 'time without time zone'],
        'timestamp'   => ['timestamp', 'timestamp with time zone', 'timestamp without time zone'],
        'binary'      => ['bytea'],
        'json'        => ['json'],
        'jsonb'       => ['jsonb']
    ];

    /**
     * Field is auto incremental.
     *
     * @var bool
     */
    protected $autoIncrement = false;

    /**
     * Indication that column have enum constrain.
     *
     * @var bool
     */
    protected $constrained = false;

    /**
     * Name of enum constraint associated with field.
     *
     * @var string
     */
    protected $constrainName = '';

    /**
     * {@inheritdoc}
     */
    public function getConstraints(): array
    {
        $constraints = parent::getConstraints();

        if ($this->constrained) {
            $constraints[] = $this->constrainName;
        }

        return $constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function getAbstractType(): string
    {
        if (!empty($this->enumValues)) {
            return 'enum';
        }

        return parent::getAbstractType();
    }

    /**
     * {@inheritdoc}
     */
    public function primary(): AbstractColumn
    {
        if (!empty($this->type) && $this->type !== 'serial') {
            //Change type of already existed column (we can't use "serial" alias here)
            $this->type = 'integer';

            return $this;
        }

        return $this->type('primary');
    }

    /**
     * {@inheritdoc}
     */
    public function bigPrimary(): AbstractColumn
    {
        if (!empty($this->type) && $this->type !== 'bigserial') {
            //Change type of already existed column (we can't use "serial" alias here)
            $this->type = 'bigint';

            return $this;
        }

        return $this->type('bigPrimary');
    }

    /**
     * {@inheritdoc}
     */
    public function enum($values): AbstractColumn
    {
        $this->enumValues = array_map('strval', is_array($values) ? $values : func_get_args());

        $this->type = 'character varying';
        foreach ($this->enumValues as $value) {
            $this->size = max((int)$this->size, strlen($value));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(DriverInterface $driver): string
    {
        $statement = parent::sqlStatement($driver);

        if ($this->getAbstractType() !== 'enum') {
            //Nothing special
            return $statement;
        }

        //We have add constraint for enum type
        $enumValues = [];
        foreach ($this->enumValues as $value) {
            $enumValues[] = $driver->quote($value);
        }

        $constrain = $driver->identifier($this->enumConstraint());
        $column = $driver->identifier($this->getName());
        $values = implode(', ', $enumValues);

        return "{$statement} CONSTRAINT {$constrain} CHECK ($column IN ({$values}))";
    }

    /**
     * Generate set of operations need to change column.
     *
     * @param DriverInterface $driver
     * @param AbstractColumn  $initial
     * @return array
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

                $type = "ALTER COLUMN {$identifier} TYPE character varying($enumSize)";
                $operations[] = $type;
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
     * @param string          $table  Table name.
     * @param array           $schema
     * @param DriverInterface $driver Postgres columns are bit more complex.
     * @return PostgresColumn
     */
    public static function createInstance(
        string $table,
        array $schema,
        DriverInterface $driver
    ): self {
        $column = new self($table, $schema['column_name'], $driver->getTimezone());

        $column->type = $schema['data_type'];
        $column->defaultValue = $schema['column_default'];
        $column->nullable = $schema['is_nullable'] === 'YES';

        if (
            is_string($column->defaultValue)
            && in_array($column->type, ['int', 'bigint', 'integer'])
            && preg_match('/nextval(.*)/', $column->defaultValue)
        ) {
            $column->type = ($column->type === 'bigint' ? 'bigserial' : 'serial');
            $column->autoIncrement = true;

            $column->defaultValue = new Fragment($column->defaultValue);

            return $column;
        }

        if (strpos($column->type, 'char') !== false && $schema['character_maximum_length']) {
            $column->size = $schema['character_maximum_length'];
        }

        if ($column->type === 'numeric') {
            $column->precision = $schema['numeric_precision'];
            $column->scale = $schema['numeric_scale'];
        }

        if ($column->type === 'USER-DEFINED' && $schema['typtype'] === 'e') {
            $column->type = $schema['typname'];

            /**
             * Attention, this is not default spiral enum type emulated via CHECK. This is real
             * Postgres enum type.
             */
            self::resolveEnum($driver, $column);
        }

        if (!empty($column->size) && strpos($column->type, 'char') !== false) {
            //Potential enum with manually created constraint (check in)
            self::resolveConstrains($driver, $schema, $column);
        }

        $column->normalizeDefault();

        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function compare(AbstractColumn $initial): bool
    {
        if (parent::compare($initial)) {
            return true;
        }

        if (
            in_array($this->getAbstractType(), ['primary', 'bigPrimary'])
            && $initial->getDefaultValue() != $this->getDefaultValue()
        ) {
            //PG adds default values to primary keys
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function quoteEnum(DriverInterface $driver): string
    {
        //Postgres enums are just constrained strings
        return '(' . $this->size . ')';
    }

    /**
     * Get/generate name for enum constraint.
     *
     * @return string
     */
    private function enumConstraint(): string
    {
        if (empty($this->constrainName)) {
            $this->constrainName = $this->table . '_' . $this->getName() . '_enum_' . uniqid();
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

            return;
        }
    }

    /**
     * Resolving enum constrain and converting it into proper enum values set.
     *
     * @param DriverInterface $driver
     * @param array           $schema
     * @param PostgresColumn  $column
     */
    private static function resolveConstrains(
        DriverInterface $driver,
        array $schema,
        PostgresColumn $column
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
     *
     * @param DriverInterface $driver
     * @param PostgresColumn  $column
     */
    private static function resolveEnum(DriverInterface $driver, PostgresColumn $column): void
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
