<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Schema;

use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Driver\HandlerInterface;
use Spiral\Database\Exception\HandlerException;
use Spiral\Database\Exception\SchemaException;
use Spiral\Database\TableInterface;

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
 * @method AbstractColumn bigInteger($column)
 * @method AbstractColumn text($column)
 * @method AbstractColumn tinyText($column)
 * @method AbstractColumn longText($column)
 * @method AbstractColumn json($column)
 * @method AbstractColumn double($column)
 * @method AbstractColumn float($column)
 * @method AbstractColumn datetime($column)
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
    public const STATUS_NEW              = 0;
    public const STATUS_EXISTS           = 1;
    public const STATUS_DECLARED_DROPPED = 2;

    /**
     * @internal
     *
     * @var DriverInterface
     */
    protected $driver = null;

    /**
     * Initial table state.
     *
     * @internal
     * @var State
     */
    protected $initial = null;

    /**
     * Currently defined table state.
     *
     * @internal
     * @var State
     */
    protected $current = null;

    /**
     * Indication that table is exists and current schema is fetched from database.
     *
     * @var int
     */
    private $status = self::STATUS_NEW;

    /**
     * Database specific tablePrefix. Required for table renames.
     *
     * @var string
     */
    private $prefix = '';

    /**
     * @param DriverInterface $driver Parent driver.
     * @param string          $name   Table name, must include table prefix.
     * @param string          $prefix Database specific table prefix.
     */
    public function __construct(DriverInterface $driver, string $name, string $prefix)
    {
        $this->driver = $driver;
        $this->prefix = $prefix;

        //Initializing states
        $this->initial = new State($this->prefix . $name);
        $this->current = new State($this->prefix . $name);

        if ($this->driver->hasTable($this->getName())) {
            $this->status = self::STATUS_EXISTS;
        }

        if ($this->exists()) {
            //Initiating table schema
            $this->initSchema($this->initial);
        }

        $this->setState($this->initial);
    }

    /**
     * Shortcut for column() method.
     *
     * @param string $column
     * @return AbstractColumn
     */
    public function __get(string $column)
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
     * @param string $type
     * @param array  $arguments Type specific parameters.
     * @return AbstractColumn
     */
    public function __call(string $type, array $arguments)
    {
        return call_user_func_array(
            [$this->column($arguments[0]), $type],
            array_slice($arguments, 1)
        );
    }

    /**
     * @return AbstractColumn|string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Cloning schemas as well.
     */
    public function __clone()
    {
        $this->initial = clone $this->initial;
        $this->current = clone $this->current;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'status'      => $this->status,
            'name'        => $this->getName(),
            'primaryKeys' => $this->getPrimaryKeys(),
            'columns'     => array_values($this->getColumns()),
            'indexes'     => array_values($this->getIndexes()),
            'foreignKeys' => array_values($this->getForeignKeys()),
        ];
    }

    /**
     * Get instance of associated driver.
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Return database specific table prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return Comparator
     */
    public function getComparator(): Comparator
    {
        return new Comparator($this->initial, $this->current);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(): bool
    {
        //Derlared as dropped != actually dropped
        return $this->status === self::STATUS_EXISTS || $this->status === self::STATUS_DECLARED_DROPPED;
    }

    /**
     * Table status (see codes above).
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Sets table name. Use this function in combination with save to rename table.
     *
     * @param string $name
     * @return string Prefixed table name.
     */
    public function setName(string $name): string
    {
        $this->current->setName($this->prefix . $name);

        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->current->getName();
    }

    /**
     * Table name before rename.
     *
     * @return string
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
        if ($this->status === self::STATUS_NEW) {
            throw new SchemaException('Unable to drop non existed table');
        }

        //Declaring as dropped
        $this->status = self::STATUS_DECLARED_DROPPED;
    }

    /**
     * Set table primary keys. Operation can only be applied for newly created tables. Now every
     * database might support compound indexes.
     *
     * @param array $columns
     * @return self
     */
    public function setPrimaryKeys(array $columns): AbstractTable
    {
        //Originally i were forcing an exception when primary key were changed, now we should
        //force it when table will be synced

        //Updating primary keys in current state
        $this->current->setPrimaryKeys($columns);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKeys(): array
    {
        return $this->current->getPrimaryKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function hasColumn(string $name): bool
    {
        return $this->current->hasColumn($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return AbstractColumn[]
     */
    public function getColumns(): array
    {
        return $this->current->getColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndex(array $columns = []): bool
    {
        return $this->current->hasIndex($columns);
    }

    /**
     * {@inheritdoc}
     *
     * @return AbstractIndex[]
     */
    public function getIndexes(): array
    {
        return $this->current->getIndexes();
    }

    /**
     * {@inheritdoc}
     */
    public function hasForeignKey(array $columns): bool
    {
        return $this->current->hasForeignKey($columns);
    }

    /**
     * {@inheritdoc}
     *
     * @return AbstractForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->current->getForeignKeys();
    }

    /**
     * {@inheritdoc}
     */
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
     * Examples:
     * $table->column('name')->string();
     *
     * @param string $name
     * @return AbstractColumn
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
     * @param array $columns List of index columns.
     * @return AbstractIndex
     *
     * @throws SchemaException
     */
    public function index(array $columns): AbstractIndex
    {
        foreach ($columns as $column) {
            if (!$this->hasColumn($column)) {
                throw new SchemaException("Undefined column '{$column}' in '{$this->getName()}'");
            }
        }

        if ($this->hasIndex($columns)) {
            return $this->current->findIndex($columns);
        }

        if ($this->initial->hasIndex($columns)) {
            //Let's ensure that index name is always stays synced (not regenerated)
            $name = $this->initial->findIndex($columns)->getName();
        } else {
            $name = $this->createIdentifier('index', $columns);
        }

        $index = $this->createIndex($name)->columns($columns);

        //Adding to current schema
        $this->current->registerIndex($index);

        return $index;
    }

    /**
     * Get/create instance of AbstractReference associated with current table based on local column
     * name.
     *
     * @param array $columns
     * @return AbstractForeignKey
     *
     * @throws SchemaException
     */
    public function foreignKey(array $columns): AbstractForeignKey
    {
        foreach ($columns as $column) {
            if (!$this->hasColumn($column)) {
                throw new SchemaException("Undefined column '{$column}' in '{$this->getName()}'");
            }
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
        $this->index($columns);

        return $foreign;
    }

    /**
     * Rename column (only if column exists).
     *
     * @param string $column
     * @param string $name New column name.
     * @return self
     *
     * @throws SchemaException
     */
    public function renameColumn(string $column, string $name): AbstractTable
    {
        if (!$this->hasColumn($column)) {
            throw new SchemaException("Undefined column '{$column}' in '{$this->getName()}'");
        }

        //Rename operation is simple about declaring new name
        $this->column($column)->setName($name);

        return $this;
    }

    /**
     * Rename index (only if index exists).
     *
     * @param array  $columns Index forming columns.
     * @param string $name    New index name.
     * @return self
     *
     * @throws SchemaException
     */
    public function renameIndex(array $columns, string $name): AbstractTable
    {
        if (!$this->hasIndex($columns)) {
            throw new SchemaException(
                "Undefined index ['" . join("', '", $columns) . "'] in '{$this->getName()}'"
            );
        }

        //Declaring new index name
        $this->index($columns)->setName($name);

        return $this;
    }

    /**
     * Drop column by it's name.
     *
     * @param string $column
     * @return self
     *
     * @throws SchemaException
     */
    public function dropColumn(string $column): AbstractTable
    {
        if (empty($schema = $this->current->findColumn($column))) {
            throw new SchemaException("Undefined column '{$column}' in '{$this->getName()}'");
        }

        //Dropping column from current schema
        $this->current->forgetColumn($schema);

        return $this;
    }

    /**
     * Drop index by it's forming columns.
     *
     * @param array $columns
     * @return self
     *
     * @throws SchemaException
     */
    public function dropIndex(array $columns): AbstractTable
    {
        if (empty($schema = $this->current->findIndex($columns))) {
            throw new SchemaException(
                "Undefined index ['" . join("', '", $columns) . "'] in '{$this->getName()}'"
            );
        }

        //Dropping index from current schema
        $this->current->forgetIndex($schema);

        return $this;
    }

    /**
     * Drop foreign key by it's name.
     *
     * @param array $columns
     * @return self
     *
     * @throws SchemaException
     */
    public function dropForeignKey(array $columns): AbstractTable
    {
        $schema = $this->current->findForeignKey($columns);
        if ($schema === null) {
            $names = join("','", $columns);
            throw new SchemaException("Undefined FK on '{$names}' in '{$this->getName()}'");
        }

        //Dropping foreign from current schema
        $this->current->forgerForeignKey($schema);

        return $this;
    }

    /**
     * Get current table state (detached).
     *
     * @return State
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
     *
     * @return self|$this
     */
    public function setState(State $state = null): AbstractTable
    {
        $this->current = new State($this->initial->getName());

        if (!empty($state)) {
            $this->current->setName($state->getName());
            $this->current->syncState($state);
        }

        return $this;
    }

    /**
     * Reset table state to it initial form.
     *
     * @return self|$this
     */
    public function resetState(): AbstractTable
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
        $handler = $this->driver->getHandler();

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
     * Check if table schema has been modified since synchronization.
     *
     * @return bool
     */
    protected function hasChanges(): bool
    {
        return $this->getComparator()->hasChanges() || $this->status === self::STATUS_DECLARED_DROPPED;
    }

    /**
     * Ensure that no wrong indexes left in table. This method will create AbstracTable
     * copy in order to prevent cross modifications.
     *
     * @param bool $withForeignKeys
     * @return AbstractTable
     */
    protected function normalizeSchema(bool $withForeignKeys = true): AbstractTable
    {
        // To make sure that no pre-sync modifications will be reflected on current table
        $target = clone $this;

        // declare all FKs dropped on tables scheduled for removal
        if ($this->status === self::STATUS_DECLARED_DROPPED) {
            foreach ($target->getForeignKeys() as $fk) {
                $target->current->forgerForeignKey($fk);
            }
        }

        /*
         * In cases where columns are removed we have to automatically remove related indexes and
         * foreign keys.
         */
        foreach ($this->getComparator()->droppedColumns() as $column) {
            foreach ($target->getIndexes() as $index) {
                if (in_array($column->getName(), $index->getColumns(), true)) {
                    $target->current->forgetIndex($index);
                }
            }

            foreach ($target->getForeignKeys() as $foreign) {
                if ($column->getName() === $foreign->getColumns()) {
                    $target->current->forgerForeignKey($foreign);
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
                if (in_array($initial->getName(), $index->getColumns(), true)) {
                    $columns = $index->getColumns();

                    //Replacing column name
                    foreach ($columns as &$column) {
                        if ($column === $initial->getName()) {
                            $column = $name->getName();
                        }

                        unset($column);
                    }

                    $targetIndex = $target->initial->findIndex($index->getColumns());
                    if (!empty($targetIndex)) {
                        //Target index got renamed or removed.
                        $targetIndex->columns($columns);
                    }

                    $index->columns($columns);
                }
            }

            foreach ($target->getForeignKeys() as $foreign) {
                $foreign->columns(array_map(function ($column) use ($initial, $name) {
                    if ($column === $initial->getName()) {
                        return $name->getName();
                    }

                    return $column;
                }, $foreign->getColumns()));
            }
        }

        if (!$withForeignKeys) {
            foreach ($this->getComparator()->addedForeignKeys() as $foreign) {
                //Excluding from creation
                $target->current->forgerForeignKey($foreign);
            }
        }

        return $target;
    }

    /**
     * Populate table schema with values from database.
     *
     * @param State $state
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
     *
     * @return array
     */
    abstract protected function fetchPrimaryKeys(): array;

    /**
     * Create column with a given name.
     *
     * @param string $name
     *
     * @return AbstractColumn
     */
    abstract protected function createColumn(string $name): AbstractColumn;

    /**
     * Create index for a given set of columns.
     *
     * @param string $name
     *
     * @return AbstractIndex
     */
    abstract protected function createIndex(string $name): AbstractIndex;

    /**
     * Create reference on a given column set.
     *
     * @param string $name
     *
     * @return AbstractForeignKey
     */
    abstract protected function createForeign(string $name): AbstractForeignKey;

    /**
     * Generate unique name for indexes and foreign keys.
     *
     * @param string $type
     * @param array  $columns
     *
     * @return string
     */
    protected function createIdentifier(string $type, array $columns): string
    {
        $name = $this->getName() . '_' . $type . '_' . join('_', $columns) . '_' . uniqid();

        if (strlen($name) > 64) {
            //Many DBMS has limitations on identifier length
            $name = md5($name);
        }

        return $name;
    }
}
