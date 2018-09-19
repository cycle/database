<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Driver\SQLServer\Schema;

use Spiral\Database\Driver;
use Spiral\Database\Schema\AbstractColumn;

/**
 * @todo investigate potential issue with entity non handling enum correctly when multiple
 * @todo column changes happen in one session (who the hell will do that?)
 */
class SQLServerColumn extends AbstractColumn
{
    /**
     * Default datetime value.
     */
    const DATETIME_DEFAULT = '1970-01-01T00:00:00';

    /**
     * Default timestamp expression (driver specific).
     */
    const DATETIME_NOW = 'getdate()';

    /**
     * Private state related values.
     */
    const EXCLUDE_FROM_COMPARE = [
        'timezone',
        'constrainedDefault',
        'defaultConstraint',
        'constrainedEnum',
        'enumConstraint'
    ];

    /**
     * {@inheritdoc}
     */
    protected $mapping = [
        //Primary sequences
        'primary'     => ['type' => 'int', 'identity' => true, 'nullable' => false],
        'bigPrimary'  => ['type' => 'bigint', 'identity' => true, 'nullable' => false],

        //Enum type (mapped via method)
        'enum'        => 'enum',

        //Logical types
        'boolean'     => 'bit',

        //Integer types (size can always be changed with size method), longInteger has method alias
        //bigInteger
        'integer'     => 'int',
        'tinyInteger' => 'tinyint',
        'bigInteger'  => 'bigint',

        //String with specified length (mapped via method)
        'string'      => 'varchar',

        //Generic types
        'text'        => ['type' => 'varchar', 'size' => 0],
        'tinyText'    => ['type' => 'varchar', 'size' => 0],
        'longText'    => ['type' => 'varchar', 'size' => 0],

        //Real types
        'double'      => 'float',
        'float'       => 'real',

        //Decimal type (mapped via method)
        'decimal'     => 'decimal',

        //Date and Time types
        'datetime'    => 'datetime',
        'date'        => 'date',
        'time'        => 'time',
        'timestamp'   => 'datetime',

        //Binary types
        'binary'      => ['type' => 'varbinary', 'size' => 0],
        'tinyBinary'  => ['type' => 'varbinary', 'size' => 0],
        'longBinary'  => ['type' => 'varbinary', 'size' => 0],

        //Additional types
        'json'        => ['type' => 'varchar', 'size' => 0],
    ];

    /**
     * {@inheritdoc}
     */
    protected $reverseMapping = [
        'primary'     => [['type' => 'int', 'identity' => true]],
        'bigPrimary'  => [['type' => 'bigint', 'identity' => true]],
        'enum'        => ['enum'],
        'boolean'     => ['bit'],
        'integer'     => ['int'],
        'tinyInteger' => ['tinyint', 'smallint'],
        'bigInteger'  => ['bigint'],
        'text'        => [['type' => 'varchar', 'size' => 0]],
        'string'      => ['varchar', 'char'],
        'double'      => ['float'],
        'float'       => ['real'],
        'decimal'     => ['decimal'],
        'timestamp'   => ['datetime'],
        'date'        => ['date'],
        'time'        => ['time'],
        'binary'      => ['varbinary'],
    ];

    /**
     * Field is table identity.
     *
     * @var bool
     */
    protected $identity = false;

    /**
     * @var bool
     */
    protected $constrainedDefault = false;

    /**
     * Name of default constraint.
     *
     * @var string
     */
    protected $defaultConstraint = '';

    /**
     * @var bool
     */
    protected $constrainedEnum = false;

    /**
     * Name of enum constraint.
     *
     * @var string
     */
    protected $enumConstraint = '';

    /**
     * {@inheritdoc}
     */
    public function getConstraints(): array
    {
        $constraints = parent::getConstraints();

        if ($this->constrainedDefault) {
            $constraints[] = $this->defaultConstraint;
        }

        if ($this->constrainedEnum) {
            $constraints[] = $this->enumConstraint;
        }

        return $constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function abstractType(): string
    {
        if (!empty($this->enumValues)) {
            return 'enum';
        }

        return parent::abstractType();
    }

    /**
     * {@inheritdoc}
     */
    public function enum($values): AbstractColumn
    {
        $this->enumValues = array_map('strval', is_array($values) ? $values : func_get_args());
        sort($this->enumValues);

        $this->type = 'varchar';
        foreach ($this->enumValues as $value) {
            $this->size = max((int)$this->size, strlen($value));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $withEnum When true enum constrain will be included into definition. Set to false
     *                       if you want to create constrain separately.
     */
    public function sqlStatement(Driver $driver, bool $withEnum = true): string
    {
        if ($withEnum && $this->abstractType() == 'enum') {
            return "{$this->sqlStatement($driver, false)} {$this->enumStatement($driver)}";
        }

        $statement = [$driver->identifier($this->getName()), $this->type];

        if (!empty($this->precision)) {
            $statement[] = "({$this->precision}, {$this->scale})";
        } elseif (!empty($this->size)) {
            $statement[] = "({$this->size})";
        } elseif ($this->type == 'varchar' || $this->type == 'varbinary') {
            $statement[] = '(max)';
        }

        if ($this->identity) {
            $statement[] = 'IDENTITY(1,1)';
        }

        $statement[] = $this->nullable ? 'NULL' : 'NOT NULL';

        if ($this->hasDefaultValue()) {
            $statement[] = "DEFAULT {$this->quoteDefault($driver)}";
        }

        return implode(' ', $statement);
    }

    /**
     * {@inheritdoc}
     */
    protected function quoteDefault(Driver $driver): string
    {
        $defaultValue = parent::quoteDefault($driver);
        if ($this->abstractType() == 'boolean') {
            $defaultValue = (int)$this->defaultValue;
        }

        return $defaultValue;
    }

    /**
     * Generate set of operations need to change column. We are expecting that column constrains
     * will be dropped separately.
     *
     * @param Driver         $driver
     * @param AbstractColumn $initial
     *
     * @return array
     */
    public function alterOperations(Driver $driver, AbstractColumn $initial): array
    {
        $operations = [];

        $currentType = [
            $this->type,
            $this->size,
            $this->precision,
            $this->scale,
            $this->nullable,
        ];

        $initType = [
            $initial->type,
            $initial->size,
            $initial->precision,
            $initial->scale,
            $initial->nullable,
        ];

        if ($currentType != $initType) {
            if ($this->abstractType() == 'enum') {
                //Getting longest value
                $enumSize = $this->size;
                foreach ($this->enumValues as $value) {
                    $enumSize = max($enumSize, strlen($value));
                }

                $type = "ALTER COLUMN {$driver->identifier($this->getName())} varchar($enumSize)";
                $operations[] = $type . ' ' . ($this->nullable ? 'NULL' : 'NOT NULL');
            } else {
                $type = "ALTER COLUMN {$driver->identifier($this->getName())} {$this->type}";

                if (!empty($this->size)) {
                    $type .= "($this->size)";
                } elseif ($this->type == 'varchar' || $this->type == 'varbinary') {
                    $type .= '(max)';
                } elseif (!empty($this->precision)) {
                    $type .= "($this->precision, $this->scale)";
                }

                $operations[] = $type . ' ' . ($this->nullable ? 'NULL' : 'NOT NULL');
            }
        }

        //Constraint should be already removed it this moment (see doColumnChange in TableSchema)
        if ($this->hasDefaultValue()) {
            $operations[] = "ADD CONSTRAINT {$this->defaultConstrain()} "
                . "DEFAULT {$this->quoteDefault($driver)} "
                . "FOR {$driver->identifier($this->getName())}";
        }

        //Constraint should be already removed it this moment (see alterColumn in SQLServerHandler)
        if ($this->abstractType() == 'enum') {
            $operations[] = "ADD {$this->enumStatement($driver)}";
        }

        return $operations;
    }

    /**
     * Get/generate name of enum constraint.
     *
     * @return string
     */
    protected function enumConstraint()
    {
        if (empty($this->enumConstraint)) {
            $this->enumConstraint = $this->table . '_' . $this->getName() . '_enum_' . uniqid();
        }

        return $this->enumConstraint;
    }

    /**
     * Get/generate name of default constrain.
     *
     * @return string
     */
    protected function defaultConstrain(): string
    {
        if (empty($this->defaultConstraint)) {
            $this->defaultConstraint = $this->table . '_' . $this->getName() . '_default_' . uniqid();
        }

        return $this->defaultConstraint;
    }

    /**
     * In SQLServer we can emulate enums similar way as in Postgres via column constrain.
     *
     * @param Driver $driver
     *
     * @return string
     */
    private function enumStatement(Driver $driver): string
    {
        $enumValues = [];
        foreach ($this->enumValues as $value) {
            $enumValues[] = $driver->quote($value);
        }

        $constrain = $driver->identifier($this->enumConstraint());
        $column = $driver->identifier($this->getName());
        $enumValues = implode(', ', $enumValues);

        return "CONSTRAINT {$constrain} CHECK ({$column} IN ({$enumValues}))";
    }

    /**
     * Normalize default value.
     */
    private function normalizeDefault()
    {
        if (!$this->hasDefaultValue()) {
            return;
        }

        if ($this->defaultValue[0] == '(' && $this->defaultValue[strlen($this->defaultValue) - 1] == ')') {
            //Cut braces
            $this->defaultValue = substr($this->defaultValue, 1, -1);
        }

        if (preg_match('/^[\'""].*?[\'"]$/', $this->defaultValue)) {
            $this->defaultValue = substr($this->defaultValue, 1, -1);
        }

        if (
            $this->phpType() != 'string'
            && ($this->defaultValue[0] == '(' && $this->defaultValue[strlen($this->defaultValue) - 1] == ')')
        ) {
            //Cut another braces
            $this->defaultValue = substr($this->defaultValue, 1, -1);
        }
    }

    /**
     * @param string $table  Table name.
     * @param array  $schema
     * @param Driver $driver SQLServer columns are bit more complex.
     *
     * @return SQLServerColumn
     */
    public static function createInstance(string $table, array $schema, Driver $driver): self
    {
        $column = new self($table, $schema['COLUMN_NAME'], $driver->getTimezone());

        $column->type = $schema['DATA_TYPE'];
        $column->nullable = strtoupper($schema['IS_NULLABLE']) == 'YES';
        $column->defaultValue = $schema['COLUMN_DEFAULT'];

        $column->identity = (bool)$schema['is_identity'];

        $column->size = (int)$schema['CHARACTER_MAXIMUM_LENGTH'];
        if ($column->size == -1) {
            $column->size = 0;
        }

        if ($column->type == 'decimal') {
            $column->precision = (int)$schema['NUMERIC_PRECISION'];
            $column->scale = (int)$schema['NUMERIC_SCALE'];
        }

        //Normalizing default value
        $column->normalizeDefault();

        /*
        * We have to fetch all column constrains cos default and enum check will be included into
        * them, plus column drop is not possible without removing all constraints.
        */

        if (!empty($schema['default_object_id'])) {
            //Looking for default constrain id
            $column->defaultConstraint = $driver->query(
                'SELECT [name] FROM [sys].[default_constraints] WHERE [object_id] = ?', [
                $schema['default_object_id'],
            ])->fetchColumn();

            if (!empty($column->defaultConstraint)) {
                $column->constrainedDefault = true;
            }
        }

        //Potential enum
        if ($column->type == 'varchar' && !empty($column->size)) {
            self::resolveEnum($driver, $schema, $column);
        }

        return $column;
    }

    /**
     * Resolve enum values if any.
     *
     * @param Driver          $driver
     * @param array           $schema
     * @param SQLServerColumn $column
     */
    private static function resolveEnum(Driver $driver, array $schema, SQLServerColumn $column)
    {
        $query = 'SELECT object_definition([o].[object_id]) AS [definition], '
            . "OBJECT_NAME([o].[object_id]) AS [name]\nFROM [sys].[objects] AS [o]\n"
            . "JOIN [sys].[sysconstraints] AS [c] ON [o].[object_id] = [c].[constid]\n"
            . "WHERE [type_desc] = 'CHECK_CONSTRAINT' AND [parent_object_id] = ? AND [c].[colid] = ?";

        $constraints = $driver->query($query, [$schema['object_id'], $schema['column_id']]);

        foreach ($constraints as $constraint) {
            $column->enumConstraint = $constraint['name'];
            $column->constrainedEnum = true;

            $name = preg_quote($driver->identifier($column->getName()));

            //We made some assumptions here...
            if (preg_match_all(
                '/' . $name . '=[\']?([^\']+)[\']?/i',
                $constraint['definition'],
                $matches
            )) {
                //Fetching enum values
                $column->enumValues = $matches[1];
                sort($column->enumValues);
            }
        }
    }
}