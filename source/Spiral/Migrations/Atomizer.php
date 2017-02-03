<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Migrations;

use Spiral\Database\Schemas\Prototypes\AbstractTable;
use Spiral\Migrations\Atomizer\RendererInterface;
use Spiral\Reactor\Body\Source;
use Spiral\Support\DFSSorter;

/**
 * Atomizer provides ability to convert given AbstractTables and their changes into set of
 * migration commands.
 */
class Atomizer
{
    /**
     * Render changes into source.
     *
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var AbstractTable[]
     */
    protected $tables = [];

    /**
     * @param RendererInterface $renderer
     */
    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Add new table into atomizer.
     *
     * @param AbstractTable $table
     *
     * @return Atomizer
     */
    public function addTable(AbstractTable $table): self
    {
        $this->tables[] = $table;

        return $this;
    }

    /**
     * Get all atomizer tables.
     *
     * @return AbstractTable[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Generate set of commands needed to describe migration (up command).
     *
     * @param Source $source
     */
    public function declareChanges(Source $source)
    {
        foreach ($this->sortedTables() as $table) {
            if (!$table->getComparator()->hasChanges()) {
                continue;
            }

            //New operations block
            $this->declareBlock($source);

            if (!$table->exists()) {
                $this->renderer->createTable($source, $table);
            } else {
                $this->renderer->updateTable($source, $table);
            }
        }
    }

    /**
     * Generate set of lines needed to rollback migration (down command).
     *
     * @param Source $source
     */
    public function revertChanges(Source $source)
    {
        foreach ($this->sortedTables(true) as $table) {
            if (!$table->getComparator()->hasChanges()) {
                continue;
            }

            //New operations block
            $this->declareBlock($source);

            if (!$table->exists()) {
                $this->renderer->dropTable($source, $table);
            } else {
                $this->renderer->revertTable($source, $table);
            }
        }
    }

    /**
     * Tables sorted in order of their dependecies.
     *
     * @param bool $reverse
     *
     * @return AbstractTable[]
     */
    protected function sortedTables($reverse = false)
    {
        /*
         * Tables has to be sorted using topological graph to execute operations in a valid order.
         */
        $sorter = new DFSSorter();
        foreach ($this->tables as $table) {
            $sorter->addItem($table->getName(), $table, $table->getDependencies());
        }

        $tables = $sorter->sort();

        if ($reverse) {
            return array_reverse($tables);
        }

        return $tables;
    }

    /**
     * Add spacing between commands, only if required.
     *
     * @param Source $source
     */
    private function declareBlock(Source $source)
    {
        if (!empty($source->getLines())) {
            $source->addLine("");
        }
    }
}