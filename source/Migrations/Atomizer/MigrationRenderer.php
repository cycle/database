<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Migrations\Atomizer;

use Spiral\Database\Schemas\Prototypes\AbstractColumn;
use Spiral\Database\Schemas\Prototypes\AbstractIndex;
use Spiral\Database\Schemas\Prototypes\AbstractReference;
use Spiral\Database\Schemas\Prototypes\AbstractTable;
use Spiral\Database\Schemas\StateComparator;
use Spiral\Reactor\Body\Source;
use Spiral\Reactor\Traits\SerializerTrait;

class MigrationRenderer implements RendererInterface
{
    use SerializerTrait;

    /**
     * Comparator alteration states.
     */
    const NEW_STATE      = 0;
    const ORIGINAL_STATE = 1;

    /**
     * @var AliasLookup
     */
    private $lookup;

    /**
     * @param AliasLookup $lookup
     */
    public function __construct(AliasLookup $lookup)
    {
        $this->lookup = $lookup;
    }

    /**
     * {@inheritdoc}
     */
    public function createTable(Source $source, AbstractTable $table)
    {
        //Get table blueprint
        $source->addLine("\$this->table({$this->table($table)})");
        $comparator = $table->getComparator();

        $this->declareColumns($source, $comparator);
        $this->declareIndexes($source, $comparator);
        $this->declareForeigns($source, $comparator, $table->getPrefix());

        if (count($table->getPrimaryKeys())) {
            $source->addString(
                "    ->setPrimaryKeys({$this->getSerializer()->serialize($table->getPrimaryKeys())})"
            );
        }

        //Finalization
        $source->addLine("    ->create();");
    }

    /**
     * {@inheritdoc}
     */
    public function updateTable(Source $source, AbstractTable $table)
    {
        //Get table blueprint
        $source->addLine("\$this->table({$this->table($table)})");

        $comparator = $table->getComparator();

        if ($comparator->isPrimaryChanged()) {
            $source->addString(
                "    ->setPrimaryKeys({$this->getSerializer()->serialize($table->getPrimaryKeys())})"
            );
        }

        $this->declareColumns($source, $comparator);
        $this->declareIndexes($source, $comparator);
        $this->declareForeigns($source, $comparator, $table->getPrefix());

        //Finalization
        $source->addLine("    ->update();");
    }

    /**
     * {@inheritdoc}
     */
    public function revertTable(Source $source, AbstractTable $table)
    {
        //Get table blueprint
        $source->addLine("\$this->table({$this->table($table)})");
        $comparator = $table->getComparator();

        $this->revertForeigns($source, $comparator, $table->getPrefix());
        $this->revertIndexes($source, $comparator);
        $this->revertColumns($source, $comparator);

        //Finalization
        $source->addLine("    ->update();");
    }

    /**
     * {@inheritdoc}
     */
    public function dropTable(Source $source, AbstractTable $table)
    {
        $source->addLine("\$this->table({$this->table($table)})->drop();");
    }

    /**
     * @param Source          $source
     * @param StateComparator $comparator
     */
    protected function declareColumns(Source $source, StateComparator $comparator)
    {
        foreach ($comparator->addedColumns() as $column) {
            $source->addString(
                "    ->addColumn('{$column->getName()}', '{$column->abstractType()}', {$this->columnOptions($column)})"
            );
        }

        foreach ($comparator->alteredColumns() as $pair) {
            $this->changeColumn($source, $pair[self::NEW_STATE], $pair[self::ORIGINAL_STATE]);
        }

        foreach ($comparator->droppedColumns() as $column) {
            $source->addString("    ->dropColumn('{$column->getName()}')");
        }
    }

    /**
     * @param Source          $source
     * @param StateComparator $comparator
     */
    protected function declareIndexes(Source $source, StateComparator $comparator)
    {
        foreach ($comparator->addedIndexes() as $index) {
            $columns = '[\'' . join('\', \'', $index->getColumns()) . '\']';
            $source->addString("    ->addIndex({$columns}, " . $this->indexOptions($index) . ")");
        }

        foreach ($comparator->alteredIndexes() as $pair) {
            /**
             * @var AbstractIndex $index
             */
            $index = $pair[self::NEW_STATE];

            $columns = '[\'' . join('\', \'', $index->getColumns()) . '\']';
            $source->addString("    ->alterIndex({$columns}, " . $this->indexOptions($index) . ")");
        }

        foreach ($comparator->droppedIndexes() as $index) {
            $columns = '[\'' . join('\', \'', $index->getColumns()) . '\']';
            $source->addString("    ->dropIndex({$columns})");
        }
    }

    /**
     * @param Source          $source
     * @param StateComparator $comparator
     * @param string          $prefix Database isolation prefix
     */
    protected function declareForeigns(
        Source $source,
        StateComparator $comparator,
        string $prefix = ''
    ) {
        foreach ($comparator->addedForeigns() as $foreign) {
            $column = "'{$foreign->getColumn()}'";
            $table = "'" . substr($foreign->getForeignTable(), strlen($prefix)) . "'";
            $key = "'{$foreign->getForeignKey()}'";

            $source->addString(
                "    ->addForeignKey({$column}, {$table}, {$key}, " . $this->foreignOptions($foreign) . ")"
            );
        }

        foreach ($comparator->alteredForeigns() as $pair) {
            /**
             * @var AbstractReference $foreign
             */
            $foreign = $pair[self::NEW_STATE];

            $column = "'{$foreign->getColumn()}'";
            $table = "'" . substr($foreign->getForeignTable(), strlen($prefix)) . "'";
            $key = "'{$foreign->getForeignKey()}'";

            $source->addString(
                "    ->alterForeignKey({$column}, {$table}, {$key}, " . $this->foreignOptions($foreign) . ")"
            );
        }

        foreach ($comparator->droppedForeigns() as $foreign) {
            $column = "'{$foreign->getColumn()}'";
            $source->addString("    ->dropForeignKey({$column})");
        }
    }

    /**
     * @param Source          $source
     * @param StateComparator $comparator
     */
    protected function revertColumns(Source $source, StateComparator $comparator)
    {
        foreach ($comparator->droppedColumns() as $column) {
            $name = "'{$column->getName()}'";
            $type = "'{$column->abstractType()}'";

            $source->addString("    ->addColumn({$name}, {$type}, {$this->columnOptions($column)})");
        }

        foreach ($comparator->alteredColumns() as $pair) {
            $this->changeColumn($source, $pair[self::ORIGINAL_STATE], $pair[self::NEW_STATE]);
        }

        foreach ($comparator->addedColumns() as $column) {
            $source->addLine("    ->dropColumn('{$column->getName()}')");
        }
    }

    /**
     * @param Source          $source
     * @param StateComparator $comparator
     */
    protected function revertIndexes(Source $source, StateComparator $comparator)
    {
        foreach ($comparator->droppedIndexes() as $index) {
            $columns = '[\'' . join('\', \'', $index->getColumns()) . '\']';
            $source->addString("    ->addIndex({$columns}, " . $this->indexOptions($index) . ")");
        }

        foreach ($comparator->alteredIndexes() as $pair) {
            /**
             * @var AbstractIndex $index
             */
            $index = $pair[self::ORIGINAL_STATE];

            $columns = '[\'' . join('\', \'', $index->getColumns()) . '\']';
            $source->addString("    ->alterIndex({$columns}, " . $this->indexOptions($index) . ")");
        }

        foreach ($comparator->addedIndexes() as $index) {
            $columns = '[\'' . join('\', \'', $index->getColumns()) . '\']';
            $source->addString("    ->dropIndex({$columns})");
        }
    }

    /**
     * @param Source          $source
     * @param StateComparator $comparator
     * @param string          $prefix Database isolation prefix.
     */
    protected function revertForeigns(
        Source $source,
        StateComparator $comparator,
        string $prefix = ''
    ) {
        foreach ($comparator->droppedForeigns() as $foreign) {
            $column = "'{$foreign->getColumn()}'";
            $table = "'" . substr($foreign->getForeignTable(), strlen($prefix)) . "'";
            $key = "'{$foreign->getForeignKey()}'";

            $source->addString(
                "    ->addForeignKey({$column}, {$table}, {$key}, " . $this->foreignOptions($foreign) . ")"
            );
        }

        foreach ($comparator->alteredForeigns() as $pair) {
            /**
             * @var AbstractReference $foreign
             */
            $foreign = $pair[self::ORIGINAL_STATE];

            $column = "'{$foreign->getColumn()}'";
            $table = "'" . substr($foreign->getForeignTable(), strlen($prefix)) . "'";
            $key = "'{$foreign->getForeignKey()}'";

            $source->addString(
                "    ->alterForeignKey({$column}, {$table}, {$key}, " . $this->foreignOptions($foreign) . ")"
            );
        }

        foreach ($comparator->addedForeigns() as $foreign) {
            $column = "'{$foreign->getColumn()}'";
            $source->addString("    ->dropForeignKey({$column})");
        }
    }

    /**
     * @param AbstractIndex $index
     *
     * @return string
     */
    private function indexOptions(AbstractIndex $index): string
    {
        $options = [
            'name'   => $index->getName(),
            'unique' => $index->isUnique()
        ];

        return $this->mountIndents($this->getSerializer()->serialize($options));
    }

    /**
     * @param AbstractReference $reference
     *
     * @return string
     */
    private function foreignOptions(AbstractReference $reference): string
    {
        $options = [
            'delete' => $reference->getDeleteRule(),
            'update' => $reference->getUpdateRule()
        ];

        return $this->mountIndents($this->getSerializer()->serialize($options));
    }

    /**
     * @param AbstractTable $table
     *
     * @return string
     */
    protected function table(AbstractTable $table): string
    {
        return "'{$this->lookup->tableAlias($table)}', '{$this->lookup->databaseAlias($table)}'";
    }

    /**
     * @param Source         $source
     * @param AbstractColumn $column
     * @param AbstractColumn $original
     */
    protected function changeColumn(
        Source $source,
        AbstractColumn $column,
        AbstractColumn $original
    ) {
        if ($column->getName() != $original->getName()) {
            $name = "'{$original->getName()}'";
        } else {
            $name = "'{$column->getName()}'";
        }

        $type = "'{$column->abstractType()}'";
        $source->addString("    ->alterColumn({$name}, {$type}, {$this->columnOptions($column)})");

        if ($column->getName() != $original->getName()) {
            $source->addString("    ->renameColumn({$name}, '{$column->getName()}')");
        }
    }

    /**
     * @param AbstractColumn $column
     *
     * @return string
     */
    private function columnOptions(AbstractColumn $column): string
    {
        $options = [
            'nullable' => $column->isNullable(),
            'default'  => $column->getDefaultValue()
        ];

        if ($column->abstractType() == 'enum') {
            $options['values'] = $column->getEnumValues();
        }

        if ($column->abstractType() == 'string') {
            $options['size'] = $column->getSize();
        }

        if ($column->abstractType() == 'decimal') {
            $options['scale'] = $column->getScale();
            $options['precision'] = $column->getPrecision();
        }

        return $this->mountIndents($this->getSerializer()->serialize($options));
    }


    /**
     * Mount indents for column and index options.
     *
     * @param $serialized
     *
     * @return string
     */
    private function mountIndents($serialized)
    {
        $lines = explode("\n", $serialized);
        foreach ($lines as &$line) {
            $line = "    " . $line;
            unset($line);
        }

        return ltrim(join("\n", $lines));
    }
}