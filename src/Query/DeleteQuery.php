<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Query;

use Spiral\Database\Driver\QueryCompiler;


/**
 * Update statement builder.
 */
class DeleteQuery extends AbstractAffect
{
    /**
     * Query type.
     */
    const QUERY_TYPE = QueryCompiler::DELETE_QUERY;

    /**
     * Change target table.
     *
     * @param string $into Table name without prefix.
     *
     * @return self
     */
    public function from(string $into): DeleteQuery
    {
        $this->table = $into;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return $this->flattenParameters($this->whereParameters);
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(QueryCompiler $compiler = null): string
    {
        if (empty($compiler)) {
            $compiler = $this->compiler->resetQuoter();
        }

        return $compiler->compileDelete($this->table, $this->whereTokens);
    }
}
