<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\Attribute\ColumnAttribute;

class SQLServerColumn extends AbstractColumn
{
    /**
     * Default datetime value.
     */
    public const DATETIME_DEFAULT = '1970-01-01T00:00:00';

    /**
     * Default timestamp expression (driver specific).
     */
    public const DATETIME_NOW = 'getdate()';

    public const DATETIME_PRECISION = 7;

    /**
     * Private state related values.
     */
    public const EXCLUDE_FROM_COMPARE = [
        'userType',
        'timezone',
        'constrainedDefault',
        'defaultConstraint',
        'constrainedEnum',
        'enumConstraint',
        'attributes',
    ];

    protected array $aliases = [
        'int'       => 'integer',
        'smallint'  => 'smallInteger',
        'bigint'    => 'bigInteger',
        'bool'      => 'boolean',
        'varbinary' => 'binary',
    ];
    protected array $mapping = [
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
        'smallInteger' => 'smallint',
        'bigInteger'  => 'bigint',

        //String with specified length (mapped via method)
        'string'      => ['type' => 'varchar', 'size' => 255],

        //Generic types
        'text'        => ['type' => 'varchar', 'size' => 0],
        'tinyText'    => ['type' => 'varchar', 'size' => 0],
        'mediumText'  => ['type' => 'varchar', 'size' => 0],
        'longText'    => ['type' => 'varchar', 'size' => 0],

        //Real types
        'double'      => 'float',
        'float'       => 'real',

        //Decimal type (mapped via method)
        'decimal'     => 'decimal',

        //Date and Time types
        'datetime'    => 'datetime',
        'datetime2'   => 'datetime2',
        'date'        => 'date',
        'time'        => 'time',
        'timestamp'   => 'datetime',

        //Binary types
        'binary'      => ['type' => 'varbinary', 'size' => 0],
        'tinyBinary'  => ['type' => 'varbinary', 'size' => 0],
        'longBinary'  => ['type' => 'varbinary', 'size' => 0],

        //Additional types
        'json'        => ['type' => 'varchar', 'size' => 0],
        'uuid'        => ['type' => 'varchar', 'size' => 36],
    ];
    protected array $reverseMapping = [
        'primary'     => [['type' => 'int', 'identity' => true]],
        'bigPrimary'  => [['type' => 'bigint', 'identity' => true]],
        'enum'        => ['enum'],
        'boolean'     => ['bit'],
        'integer'     => ['int'],
        'tinyInteger' => ['tinyint'],
        'smallInteger' => ['smallint'],
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

    #[ColumnAttribute(['varchar', 'datetime2', 'varbinary'])]
    protected int $size = 0;

    /**
     * Field is table identity.
     */
    protected bool $identity = false;

    protected bool $constrainedDefault = false;

    /**
     * Name of default constraint.
     */
    protected string $defaultConstraint = '';

    protected bool $constrainedEnum = false;

    /**
     * Name of enum constraint.
     */
    protected string $enumConstraint = '';

    /**
     * @psalm-param non-empty-string $table Table name.
     *
     * @param DriverInterface $driver SQLServer columns are bit more complex.
     */
    public static function createInstance(
        string $table,
        array $schema,
        DriverInterface $driver,
    ): self {
        $column = new self($table, $schema['COLUMN_NAME'], $driver->getTimezone());

        $column->type = $schema['DATA_TYPE'];
        $column->nullable = \strtoupper($schema['IS_NULLABLE']) === 'YES';
        $column->defaultValue = $schema['COLUMN_DEFAULT'];

        $column->identity = (bool) $schema['is_identity'];

        $column->size = (int) $schema['CHARACTER_MAXIMUM_LENGTH'];
        if ($column->size === -1) {
            $column->size = 0;
        }

        if ($column->type === 'decimal') {
            $column->precision = (int) $schema['NUMERIC_PRECISION'];
            $column->scale = (int) $schema['NUMERIC_SCALE'];
        }

        if ($column->type === 'datetime2') {
            $column->size = (int) $schema['DATETIME_PRECISION'];
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
                'SELECT [name] FROM [sys].[default_constraints] WHERE [object_id] = ?',
                [
                    $schema['default_object_id'],
                ],
            )->fetchColumn();

            if (!empty($column->defaultConstraint)) {
                $column->constrainedDefault = true;
            }
        }

        //Potential enum
        if ($column->type === 'varchar' && !empty($column->size)) {
            self::resolveEnum($driver, $schema, $column);
        }

        return $column;
    }

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

    public function getAbstractType(): string
    {
        return !empty($this->enumValues) ? 'enum' : parent::getAbstractType();
    }

    public function enum(mixed $values): AbstractColumn
    {
        $this->enumValues = \array_map('strval', \is_array($values) ? $values : \func_get_args());
        \sort($this->enumValues);

        $this->type = 'varchar';
        foreach ($this->enumValues as $value) {
            $this->size = \max((int) $this->size, \strlen($value));
        }

        return $this;
    }

    public function datetime(int $size = 0, mixed ...$attributes): self
    {
        $size === 0 ? $this->type('datetime') : $this->type('datetime2');
        $this->fillAttributes($attributes);

        ($size < 0 || $size > static::DATETIME_PRECISION) && throw new SchemaException(
            \sprintf('Invalid %s precision value.', $this->getAbstractType()),
        );
        $this->size = $size;

        return $this;
    }

    /**
     * @param bool $withEnum When true enum constrain will be included into definition. Set to false
     *                       if you want to create constrain separately.
     *
     * @psalm-return non-empty-string
     */
    public function sqlStatement(DriverInterface $driver, bool $withEnum = true): string
    {
        if ($withEnum && $this->getAbstractType() === 'enum') {
            return "{$this->sqlStatement($driver, false)} {$this->enumStatement($driver)}";
        }

        $statement = [$driver->identifier($this->getName()), $this->type];

        if (!empty($this->precision)) {
            $statement[] = "({$this->precision}, {$this->scale})";
        } elseif (!empty($this->size)) {
            $statement[] = "({$this->size})";
        } elseif ($this->type === 'varchar' || $this->type === 'varbinary') {
            $statement[] = '(max)';
        }

        if ($this->identity) {
            $statement[] = 'IDENTITY(1,1)';
        }

        $statement[] = $this->nullable ? 'NULL' : 'NOT NULL';

        if ($this->hasDefaultValue()) {
            $statement[] = "DEFAULT {$this->quoteDefault($driver)}";
        }

        return \implode(' ', $statement);
    }

    /**
     * Generate set of operations need to change column. We are expecting that column constrains
     * will be dropped separately.
     *
     * @return string[]
     */
    public function alterOperations(DriverInterface $driver, AbstractColumn $initial): array
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

        if ($currentType !== $initType) {
            if ($this->getAbstractType() === 'enum') {
                //Getting longest value
                $enumSize = $this->size;
                foreach ($this->enumValues as $value) {
                    $enumSize = \max($enumSize, \strlen($value));
                }

                $type = "ALTER COLUMN {$driver->identifier($this->getName())} varchar($enumSize)";
                $operations[] = $type . ' ' . ($this->nullable ? 'NULL' : 'NOT NULL');
            } else {
                $type = "ALTER COLUMN {$driver->identifier($this->getName())} {$this->type}";

                if (!empty($this->size)) {
                    $type .= "($this->size)";
                } elseif ($this->type === 'varchar' || $this->type === 'varbinary') {
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
        if ($this->getAbstractType() === 'enum') {
            $operations[] = "ADD {$this->enumStatement($driver)}";
        }

        return $operations;
    }

    protected static function isJson(AbstractColumn $column): ?bool
    {
        // In SQL Server, we cannot determine if a column has a JSON type.
        return $column->getAbstractType() === 'text' ? null : false;
    }

    /**
     * @psalm-return non-empty-string
     */
    protected function quoteDefault(DriverInterface $driver): string
    {
        $defaultValue = parent::quoteDefault($driver);
        if ($this->getAbstractType() === 'boolean') {
            $defaultValue = (string) ((int) $this->defaultValue);
        }

        return $defaultValue;
    }

    /**
     * Get/generate name of enum constraint.
     */
    protected function enumConstraint(): string
    {
        if (empty($this->enumConstraint)) {
            $this->enumConstraint = $this->table . '_' . $this->getName() . '_enum_' . \uniqid();
        }

        return $this->enumConstraint;
    }

    /**
     * Get/generate name of default constrain.
     */
    protected function defaultConstrain(): string
    {
        if (empty($this->defaultConstraint)) {
            $this->defaultConstraint = $this->table . '_' . $this->getName() . '_default_' . \uniqid();
        }

        return $this->defaultConstraint;
    }

    /**
     * Resolve enum values if any.
     */
    private static function resolveEnum(
        DriverInterface $driver,
        array $schema,
        self $column,
    ): void {
        $query = 'SELECT object_definition([o].[object_id]) AS [definition], '
            . "OBJECT_NAME([o].[object_id]) AS [name]\nFROM [sys].[objects] AS [o]\n"
            . "JOIN [sys].[sysconstraints] AS [c] ON [o].[object_id] = [c].[constid]\n"
            . "WHERE [type_desc] = 'CHECK_CONSTRAINT' AND [parent_object_id] = ? AND [c].[colid] = ?";

        $constraints = $driver->query($query, [$schema['object_id'], $schema['column_id']]);

        foreach ($constraints as $constraint) {
            $column->enumConstraint = $constraint['name'];
            $column->constrainedEnum = true;

            $name = \preg_quote($driver->identifier($column->getName()));

            // we made some assumptions here...
            if (
                \preg_match_all(
                    '/' . $name . '=[\']?([^\']+)[\']?/i',
                    $constraint['definition'],
                    $matches,
                )
            ) {
                //Fetching enum values
                $column->enumValues = $matches[1];
                \sort($column->enumValues);
            }
        }
    }

    /**
     * In SQLServer we can emulate enums similar way as in Postgres via column constrain.
     *
     * @psalm-return non-empty-string
     */
    private function enumStatement(DriverInterface $driver): string
    {
        $enumValues = [];
        foreach ($this->enumValues as $value) {
            $enumValues[] = $driver->quote($value);
        }

        $constrain = $driver->identifier($this->enumConstraint());
        $column = $driver->identifier($this->getName());
        $enumValues = \implode(', ', $enumValues);

        return "CONSTRAINT {$constrain} CHECK ({$column} IN ({$enumValues}))";
    }

    /**
     * Normalize default value.
     */
    private function normalizeDefault(): void
    {
        if (!$this->hasDefaultValue()) {
            return;
        }

        if ($this->defaultValue[0] === '(' && $this->defaultValue[\strlen($this->defaultValue) - 1] === ')') {
            //Cut braces
            $this->defaultValue = \substr($this->defaultValue, 1, -1);
        }

        if (\preg_match('/^[\'"].*?[\'"]$/', $this->defaultValue)) {
            $this->defaultValue = \substr($this->defaultValue, 1, -1);
        }

        if (
            $this->getType() !== 'string'
            && (
                $this->defaultValue[0] === '('
                && $this->defaultValue[\strlen($this->defaultValue) - 1] === ')'
            )
        ) {
            //Cut another braces
            $this->defaultValue = \substr($this->defaultValue, 1, -1);
        }
    }
}
