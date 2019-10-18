<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\Postgres;

use Spiral\Database\Driver\Compiler as AbstractCompiler;
use Spiral\Database\Driver\QueryBindings;

/**
 * Postgres syntax specific compiler.
 */
class PostgresCompiler extends AbstractCompiler
{
    /**
     * {@inheritdoc}
     */
    public function compileInsert(
        QueryBindings $bindings,
        string $table,
        array $columns,
        array $rowsets,
        string $primaryKey = null
    ): string {
        return sprintf(
            '%s%s',
            parent::compileInsert(
                $bindings,
                $table,
                $columns,
                $rowsets
            ),
            (!empty($primaryKey) ? ' RETURNING ' . $this->quote($bindings, $primaryKey) : '')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function compileDistinct(QueryBindings $bindings, $distinct): string
    {
        if (empty($distinct)) {
            return '';
        }

        return 'DISTINCT' . (is_string($distinct) ? '(' . $this->quote($bindings, $distinct) . ')' : '');
    }
}
