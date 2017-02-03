<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Migrations\Atomizer;

use Spiral\Database\Schemas\Prototypes\AbstractTable;
use Spiral\Reactor\Body\Source;

/**
 * Renders table differences and create syntaxes into given source.
 */
interface RendererInterface
{
    /**
     * Migration engine specific table creation syntax.
     *
     * @param Source        $source
     * @param AbstractTable $table
     */
    public function createTable(Source $source, AbstractTable $table);

    /**
     * Migration engine specific table update syntax.
     *
     * @param Source        $source
     * @param AbstractTable $table
     */
    public function updateTable(Source $source, AbstractTable $table);

    /**
     * Migration engine specific table revert syntax.
     *
     * @param Source        $source
     * @param AbstractTable $table
     */
    public function revertTable(Source $source, AbstractTable $table);

    /**
     * Migration engine specific table drop syntax.
     *
     * @param Source        $source
     * @param AbstractTable $table
     */
    public function dropTable(Source $source, AbstractTable $table);
}