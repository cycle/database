<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\HandlerException;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\TableInterface;

/**
 * AbstractTable class used to describe and manage state of specified table. It provides ability to
 * get table introspection, update table schema and automatically generate set of diff operations.
 *
 * Most of table operation like column, index or foreign key creation/altering will be applied when
 * save() method will be called.
 *
 * Column configuration shortcuts:
 *
 * @method AbstractColumn primary($column)
 * @method AbstractColumn bigPrimary($column)
 * @method AbstractColumn enum($column, array $values)
 * @method AbstractColumn string($column, $length = 255)
 * @method AbstractColumn decimal($column, $precision, $scale)
 * @method AbstractColumn boolean($column)
 * @method AbstractColumn integer($column)
 * @method AbstractColumn tinyInteger($column)
 * @method AbstractColumn smallInteger($column)
 * @method AbstractColumn bigInteger($column)
 * @method AbstractColumn text($column)
 * @method AbstractColumn tinyText($column)
 * @method AbstractColumn mediumText($column)
 * @method AbstractColumn longText($column)
 * @method AbstractColumn json($column)
 * @method AbstractColumn double($column)
 * @method AbstractColumn float($column)
 * @method AbstractColumn datetime($column, $size = 0)
 * @method AbstractColumn date($column)
 * @method AbstractColumn time($column)
 * @method AbstractColumn timestamp($column)
 * @method AbstractColumn binary($column)
 * @method AbstractColumn tinyBinary($column)
 * @method AbstractColumn longBinary($column)
 * @method AbstractColumn uuid($column)
 */
abstract class AbstractTable implements TableInterface, ElementInterface
{
    /**
     * Table states.
     */
    public const STATUS_NEW = 0;

    public const STATUS_EXISTS = 1;
    public const STATUS_DECLARED_DROPPED = 2;

    /**
     * Initial table state.
     *
     * @internal
     */
    protected State $initial;

    /**
     * Currently defined table state.
     *
     * @internal
     */
    protected State $current;

    /**
     * Indication that table is exists and current schema is fetched from database.
     */
    private int $status = self::STATUS_NEW;

    /**
     * @param DriverInterface $driver Parent driver.
     *
     * @psalm-param non-empty-string $name Table name, must include table prefix.
     *
     * @param string $prefix Database specific table prefix. Required for table renames.
     */
    public function __construct(
        protected DriverInterface $driver,
        string $name,
        private string $prefix,
    ) {
        //Initializing states
        $prefixedName = $this->prefixTableName($name);
        $this->initial = new State($prefixedName);
        $this->current = new State($prefixedName);

        if ($this->driver->getSchemaHandler()->hasTable($this->getFullName())) {
            $this->status = self::STATUS_EXISTS;
        }

        if ($this->exists()) {
            //Initiating table schema
            $this->initSchema($this->initial);
        }

        $this->setState($this->initial);
    }

    /**
     * Sanitize column expression for index name
     *
     * @psalm-param non-empty-string $column
     *
     * @psalm-return non-empty-string
     */
    public static function sanitizeColumnExpression(string $column): string
    {
        return \preg_replace(['/\(/', '/\)/', '/ /'], '__', \strtolower($column));
    }

    /**
     * Get instance of associated driver.
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Return database specific table prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getComparator(): ComparatorInterface
    {
        return new Comparator($this->initial, $this->current);
    }

    public function exists(): bool
    {
        // Declared as dropped != actually dropped
        return $this->status === self::STATUS_EXISTS || $this->status === self::STATUS_DECLARED_DROPPED;
    }

    /**
     * Table status (see codes above).
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Sets table name. Use this function in combination with save to rename table.
     *
     * @psalm-param non-empty-string $name
     *
     * @psalm-return non-empty-string Prefixed table name.
     */
    public function setName(string $name): string
    {
        $this->current->setName($this->prefixTableName($name));

        return $this->getFullName();
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getName(): string
    {
        return $this->getFullName();
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getFullName(): string
    {
        return $this->current->getName();
    }

    /**
     * Table name before rename.
     *
     * @psalm-return non-empty-string
     */
    public function getInitialName(): string
    {
        return $this->initial->getName();
    }

    /**
     * Declare table as dropped, you have to sync table using "save" method in order to apply this
     * change.
     *
     * Attention, method will flush declared FKs to ensure that table express no dependecies.
     */
    public function declareDropped(): void
    {
        $this->status === self::STATUS_NEW and throw new SchemaException('Unable to drop non existed table');

        //Declaring as dropped
        $this->status = self::STATUS_DECLARED_DROPPED;
    }

    /**
     * Set table primary keys. Operation can only be applied for newly created tables. Now every
     * database might support compound indexes.
     */
    public function setPrimaryKeys(array $columns): self
    {
        //Originally i were forcing an exception when primary key were changed, now we should
        //force it when table will be synced

        //Updating primary keys in current state
        $this->current->setPrimaryKeys($columns);

        return $this;
    }

    public function getPrimaryKeys(): array
    {
        return $this->current->getPrimaryKeys();
    }

    public function hasColumn(string $name): bool
    {
        return $this->current->hasColumn($name);
    }

    /**
     * @return AbstractColumn[]
     */
    public function getColumns(): array
    {
        return $this->current->getColumns();
    }

    public function hasIndex(array $columns = []): bool
    {
        return $this->current->hasIndex($columns);
    }

    /**
     * @return AbstractIndex[]
     */
    public function getIndexes(): array
    {
        return $this->current->getIndexes();
    }

    public function hasForeignKey(array $columns): bool
    {
        return $this->current->hasForeignKey($columns);
    }

    /**
     * @return AbstractForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->current->getForeignKeys();
    }

    public function getDependencies(): array
    {
        $tables = [];
        foreach ($this->current->getForeignKeys() as $foreignKey) {
            $tables[] = $foreignKey->getForeignTable();
        }

        return $tables;
    }

    /**
     * Get/create instance of AbstractColumn associated with current table.
     *
     * Attention, renamed column will be available by it's old name until being synced!
     *
     * @psalm-param non-empty-string $name
     *
     * Examples:
     * $table->column('name')->string();
     */
    public function column(string $name): AbstractColumn
    {
        if ($this->current->hasColumn($name)) {
            //Column already exists
            return $this->current->findColumn($name);
        }

        if ($this->initial->hasColumn($name)) {
            //Fetch from initial state (this code is required to ensure column states after schema
            //flushing)
            $column = clone $this->initial->findColumn($name);
        } else {
            $column = $this->createColumn($name);
        }

        $this->current->registerColumn($column);

        return $column;
    }

    /**
     * Get/create instance of AbstractIndex associated with current table based on list of forming
     * column names.
     *
     * Example:
     * $table->index(['key']);
     * $table->index(['key', 'key2']);
     *
     * @param array $columns List of index columns
     *
     * @throws SchemaException
     * @throws DriverException
     */
    public function index(array $columns): AbstractIndex
    {
        $original = $columns;
        $normalized = [];
        $sort = [];

        foreach ($columns as $expression) {
            [$column, $order] = AbstractIndex::parseColumn($expression);

            // If expression like 'column DESC' was passed, we cast it to 'column' => 'DESC'
            if ($order !== null) {
                $this->isIndexColumnSortingSupported() or throw new DriverException(\sprintf(
                    'Failed to create index with `%s` on `%s`, column sorting is not supported',
                    $expression,
                    $this->getFullName(),
                ));

                $sort[$column] = $order;
            }

            $normalized[] = $column;
        }
        $columns = $normalized;

        foreach ($columns as $column) {
            $this->hasColumn($column) or throw new SchemaException(
                "Undefined column '{$column}' in '{$this->getFullName()}'",
            );
        }

        if ($this->hasIndex($original)) {
            return $this->current->findIndex($original);
        }

        if ($this->initial->hasIndex($original)) {
            //Let's ensure that index name is always stays synced (not regenerated)
            $name = $this->initial->findIndex($original)->getName();
        } else {
            $name = $this->createIdentifier('index', $original);
        }

        $index = $this->createIndex($name)->columns($columns)->sort($sort);

        //Adding to current schema
        $this->current->registerIndex($index);

        return $index;
    }

    /**
     * Get/create instance of AbstractReference associated with current table based on local column
     * name.
     *
     * @throws SchemaException
     */
    public function foreignKey(array $columns, bool $indexCreate = true): AbstractForeignKey
    {
        foreach ($columns as $column) {
            $this->hasColumn($column) or throw new SchemaException(
                "Undefined column '{$column}' in '{$this->getFullName()}'",
            );
        }

        if ($this->hasForeignKey($columns)) {
            return $this->current->findForeignKey($columns);
        }

        if ($this->initial->hasForeignKey($columns)) {
            //Let's ensure that FK name is always stays synced (not regenerated)
            $name = $this->initial->findForeignKey($columns)->getName();
        } else {
            $name = $this->createIdentifier('foreign', $columns);
        }

        $foreign = $this->createForeign($name)->columns($columns);

        //Adding to current schema
        $this->current->registerForeignKey($foreign);

        //Let's ensure index existence to performance and compatibility reasons
        $indexCreate ? $this->index($columns) : $foreign->setIndex(false);

        return $foreign;
    }

    /**
     * Rename column (only if column exists).
     *
     * @psalm-param non-empty-string $column
     * @psalm-param non-empty-string $name New column name.
     *
     * @throws SchemaException
     */
    public function renameColumn(string $column, string $name): self
    {
        $this->hasColumn($column) or throw new SchemaException(
            "Undefined column '{$column}' in '{$this->getFullName()}'",
        );

        //Rename operation is simple about declaring new name
        $this->column($column)->setName($name);

        return $this;
    }

    /**
     * Rename index (only if index exists).
     *
     * @param array $columns Index forming columns.
     *
     * @psalm-param non-empty-string $name New index name.
     *
     * @throws SchemaException
     */
    public function renameIndex(array $columns, string $name): self
    {
        $this->hasIndex($columns) or throw new SchemaException(
            "Undefined index ['" . \implode("', '", $columns) . "'] in '{$this->getFullName()}'",
        );

        //Declaring new index name
        $this->index($columns)->setName($name);

        return $this;
    }

    /**
     * Drop column by it's name.
     *
     * @psalm-param non-empty-string $column
     *
     * @throws SchemaException
     */
    public function dropColumn(string $column): self
    {
        $schema = $this->current->findColumn($column);
        $schema === null and throw new SchemaException("Undefined column '{$column}' in '{$this->getFullName()}'");

        //Dropping column from current schema
        $this->current->forgetColumn($schema);

        return $this;
    }

    /**
     * Drop index by it's forming columns.
     *
     * @throws SchemaException
     */
    public function dropIndex(array $columns): self
    {
        $schema = $this->current->findIndex($columns);
        $schema === null and throw new SchemaException(
            "Undefined index ['" . \implode("', '", $columns) . "'] in '{$this->getFullName()}'",
        );

        //Dropping index from current schema
        $this->current->forgetIndex($schema);

        return $this;
    }

    /**
     * Drop foreign key by it's name.
     *
     * @throws SchemaException
     */
    public function dropForeignKey(array $columns): self
    {
        $schema = $this->current->findForeignKey($columns);
        if ($schema === null) {
            $names = \implode("','", $columns);
            throw new SchemaException("Undefined FK on '{$names}' in '{$this->getFullName()}'");
        }

        //Dropping foreign from current schema
        $this->current->forgetForeignKey($schema);

        return $this;
    }

    /**
     * Get current table state (detached).
     */
    public function getState(): State
    {
        $state = clone $this->current;
        $state->remountElements();

        return $state;
    }

    /**
     * Reset table state to new form.
     *
     * @param State $state Use null to flush table schema.
     */
    public function setState(?State $state = null): self
    {
        $this->current = new State($this->initial->getName());

        if ($state !== null) {
            $this->current->setName($state->getName());
            $this->current->syncState($state);
        }

        return $this;
    }

    /**
     * Reset table state to it initial form.
     */
    public function resetState(): self
    {
        $this->setState($this->initial);

        return $this;
    }

    /**
     * Save table schema including every column, index, foreign key creation/altering. If table
     * does not exist it must be created. If table declared as dropped it will be removed from
     * the database.
     *
     * @param int  $operation Operation to be performed while table being saved. In some cases
     *                        (when multiple tables are being updated) it is reasonable to drop
     *                        foreign keys and indexes prior to dropping related columns. See sync
     *                        bus class to get more details.
     * @param bool $reset     When true schema will be marked as synced.
     *
     * @throws HandlerException
     * @throws SchemaException
     */
    public function save(int $operation = HandlerInterface::DO_ALL, bool $reset = true): void
    {
        // We need an instance of Handler of dbal operations
        $handler = $this->driver->getSchemaHandler();

        if ($this->status === self::STATUS_DECLARED_DROPPED && $operation & HandlerInterface::DO_DROP) {
            //We don't need reflector for this operation
            $handler->dropTable($this);

            //Flushing status
            $this->status = self::STATUS_NEW;

            return;
        }

        // Ensure that columns references to valid indexes and et
        $prepared = $this->normalizeSchema(($operation & HandlerInterface::CREATE_FOREIGN_KEYS) !== 0);

        if ($this->status === self::STATUS_NEW) {
            //Executing table creation
            $handler->createTable($prepared);
        } else {
            //Executing table syncing
            if ($this->hasChanges()) {
                $handler->syncTable($prepared, $operation);
            }
        }

        // Syncing our schemas
        if ($reset) {
            $this->status = self::STATUS_EXISTS;
            $this->initial->syncState($prepared->current);
        }
    }

    /**
     * Shortcut for column() method.
     *
     * @psalm-param non-empty-string $column
     */
    public function __get(string $column): AbstractColumn
    {
        return $this->column($column);
    }

    /**
     * Column creation/altering shortcut, call chain is identical to:
     * AbstractTable->column($name)->$type($arguments).
     *
     * Example:
     * $table->string("name");
     * $table->text("some_column");
     *
     * @psalm-param non-empty-string $type
     *
     * @param array $arguments Type specific parameters.
     */
    public function __call(string $type, array $arguments): AbstractColumn
    {
        return \call_user_func_array(
            [$this->column($arguments[0]), $type],
            \array_slice($arguments, 1),
        );
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    /**
     * Cloning schemas as well.
     */
    public function __clone()
    {
        $this->initial = clone $this->initial;
        $this->current = clone $this->current;
    }

    public function __debugInfo(): array
    {
        return [
            'status'      => $this->status,
            'full_name'   => $this->getFullName(),
            'name'        => $this->getName(),
            'primaryKeys' => $this->getPrimaryKeys(),
            'columns'     => \array_values($this->getColumns()),
            'indexes'     => \array_values($this->getIndexes()),
            'foreignKeys' => \array_values($this->getForeignKeys()),
        ];
    }

    /**
     * Check if table schema has been modified since synchronization.
     */
    protected function hasChanges(): bool
    {
        return $this->getComparator()->hasChanges() || $this->status === self::STATUS_DECLARED_DROPPED;
    }

    /**
     * Add prefix to a given table name
     *
     * @psalm-param non-empty-string $column
     *
     * @psalm-return non-empty-string
     */
    protected function prefixTableName(string $name): string
    {
        return $this->prefix . $name;
    }

    /**
     * Ensure that no wrong indexes left in table. This method will create AbstractTable
     * copy in order to prevent cross modifications.
     */
    protected function normalizeSchema(bool $withForeignKeys = true): self
    {
        // To make sure that no pre-sync modifications will be reflected on current table
        $target = clone $this;

        // declare all FKs dropped on tables scheduled for removal
        if ($this->status === self::STATUS_DECLARED_DROPPED) {
            foreach ($target->getForeignKeys() as $fk) {
                $target->current->forgetForeignKey($fk);
            }
        }

        /*
         * In cases where columns are removed we have to automatically remove related indexes and
         * foreign keys.
         */
        foreach ($this->getComparator()->droppedColumns() as $column) {
            foreach ($target->getIndexes() as $index) {
                if (\in_array($column->getName(), $index->getColumns(), true)) {
                    $target->current->forgetIndex($index);
                }
            }

            foreach ($target->getForeignKeys() as $foreign) {
                if ($column->getName() === $foreign->getColumns()) {
                    $target->current->forgetForeignKey($foreign);
                }
            }
        }

        //We also have to adjusts indexes and foreign keys
        foreach ($this->getComparator()->alteredColumns() as $pair) {
            /**
             * @var AbstractColumn $initial
             * @var AbstractColumn $name
             */
            [$name, $initial] = $pair;

            foreach ($target->getIndexes() as $index) {
                if (\in_array($initial->getName(), $index->getColumns(), true)) {
                    $columns = $index->getColumns();

                    //Replacing column name
                    foreach ($columns as &$column) {
                        if ($column === $initial->getName()) {
                            $column = $name->getName();
                        }

                        unset($column);
                    }
                    unset($column);

                    $targetIndex = $target->initial->findIndex($index->getColumns());
                    if ($targetIndex !== null) {
                        //Target index got renamed or removed.
                        $targetIndex->columns($columns);
                    }

                    $index->columns($columns);
                }
            }

            foreach ($target->getForeignKeys() as $foreign) {
                $foreign->columns(
                    \array_map(
                        static fn($column) => $column === $initial->getName() ? $name->getName() : $column,
                        $foreign->getColumns(),
                    ),
                );
            }
        }

        if (!$withForeignKeys) {
            foreach ($this->getComparator()->addedForeignKeys() as $foreign) {
                //Excluding from creation
                $target->current->forgetForeignKey($foreign);
            }
        }

        return $target;
    }

    /**
     * Populate table schema with values from database.
     */
    protected function initSchema(State $state): void
    {
        foreach ($this->fetchColumns() as $column) {
            $state->registerColumn($column);
        }

        foreach ($this->fetchIndexes() as $index) {
            $state->registerIndex($index);
        }

        foreach ($this->fetchReferences() as $foreign) {
            $state->registerForeignKey($foreign);
        }

        $state->setPrimaryKeys($this->fetchPrimaryKeys());
        //DBMS specific initialization can be placed here
    }

    protected function isIndexColumnSortingSupported(): bool
    {
        return true;
    }

    /**
     * Fetch index declarations from database.
     *
     * @return AbstractColumn[]
     */
    abstract protected function fetchColumns(): array;

    /**
     * Fetch index declarations from database.
     *
     * @return AbstractIndex[]
     */
    abstract protected function fetchIndexes(): array;

    /**
     * Fetch references declaration from database.
     *
     * @return AbstractForeignKey[]
     */
    abstract protected function fetchReferences(): array;

    /**
     * Fetch names of primary keys from table.
     */
    abstract protected function fetchPrimaryKeys(): array;

    /**
     * Create column with a given name.
     *
     * @psalm-param non-empty-string $name
     *
     */
    abstract protected function createColumn(string $name): AbstractColumn;

    /**
     * Create index for a given set of columns.
     *
     * @psalm-param non-empty-string $name
     */
    abstract protected function createIndex(string $name): AbstractIndex;

    /**
     * Create reference on a given column set.
     *
     * @psalm-param non-empty-string $name
     */
    abstract protected function createForeign(string $name): AbstractForeignKey;

    /**
     * Generate unique name for indexes and foreign keys.
     *
     * @psalm-param non-empty-string $type
     */
    protected function createIdentifier(string $type, array $columns): string
    {
        // Sanitize columns in case they have expressions
        $sanitized = [];
        foreach ($columns as $column) {
            $sanitized[] = self::sanitizeColumnExpression($column);
        }

        $name = $this->getFullName()
            . '_' . $type
            . '_' . \implode('_', $sanitized)
            . '_' . \uniqid();

        if (\strlen($name) > 64) {
            //Many DBMS has limitations on identifier length
            $name = \md5($name);
        }

        return $name;
    }
}
