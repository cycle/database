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
use Spiral\Database\Injection\ParameterInterface;

/**
 * Postgres syntax specific compiler.
 */
class PostgresCompiler extends AbstractCompiler
{
    /**
     * {@inheritdoc}
     */
    public function compileInsert(
        string $table,
        array $columns,
        array $rowsets,
        string $primaryKey = null
    ): string {
        return parent::compileInsert(
                $table,
                $columns,
                $rowsets
            ) . (!empty($primaryKey) ? ' RETURNING ' . $this->quote($primaryKey) : '');
    }

    /**
     * {@inheritdoc}
     */
    protected function compileDistinct($distinct): string
    {
        if (empty($distinct)) {
            return '';
        }

        return 'DISTINCT' . (is_string($distinct) ? '(' . $this->quote($distinct) . ')' : '');
    }

    /**
     * Resolve operator value based on value value. ;).
     *
     * @param mixed  $parameter
     * @param string $operator
     *
     * @return string
     */
    protected function prepareOperator($parameter, string $operator): string
    {
        if (!$parameter instanceof ParameterInterface) {
            //Probably fragment
            return $operator;
        }

        if ($operator != '=' || is_scalar($parameter->getValue())) {
            //Doing nothing for non equal operators
            return $operator;
        }

        if ($parameter->isArray()) {
            //Automatically switching between equal and IN
            return 'IN';
        }

        return $operator;
    }
}
