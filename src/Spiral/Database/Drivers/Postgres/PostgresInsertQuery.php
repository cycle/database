<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Drivers\Postgres;

use Spiral\Database\Builders\InsertQuery;
use Spiral\Database\Entities\QueryCompiler as AbstractCompiler;
use Spiral\Database\Exceptions\BuilderException;

/**
 * Postgres driver requires little bit different way to handle last insert id.
 */
class PostgresInsertQuery extends InsertQuery
{
    /**
     * {@inheritdoc}
     *
     * @throws BuilderException
     */
    public function sqlStatement(AbstractCompiler $compiler = null): string
    {
        if (
            !$this->driver instanceof PostgresDriver
            || (!empty($compiler) && !$compiler instanceof PostgresCompiler)
        ) {
            throw new BuilderException(
                'Postgres InsertQuery can be used only with Postgres driver and compiler'
            );
        }

        if (empty($compiler)) {
            $compiler = $this->compiler->resetQuoter();
        }

        /**
         * @var PostgresDriver   $driver
         * @var PostgresCompiler $compiler
         */
        return $compiler->compileInsert(
            $this->table,
            $this->columns,
            $this->rowsets,
            $this->driver->getPrimary($this->compiler->getPrefix(), $this->table)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        return (int)$this->driver->statement(
            $this->sqlStatement(),
            $this->getParameters()
        )->fetchColumn();
    }
}
