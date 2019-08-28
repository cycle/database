<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Database\Driver\SQLite;

use Spiral\Database\Driver\Compiler as AbstractCompiler;
use Spiral\Database\Injection\ParameterInterface;

/**
 * SQLite specific syntax compiler.
 */
class SQLiteCompiler extends AbstractCompiler
{
    /**
     * {@inheritdoc}
     *
     * @see http://stackoverflow.com/questions/1609637/is-it-possible-to-insert-multiple-rows-at-a-time-in-an-sqlite-database
     */
    public function compileInsert(string $table, array $columns, array $rowsets): string
    {
        //@todo possibly different statement for versions higher than 3.7.11
        if (count($rowsets) == 1) {
            return parent::compileInsert($table, $columns, $rowsets);
        }

        //SQLite uses alternative syntax
        $statement = [];
        $statement[] = "INSERT INTO {$this->quote($table, true)} ({$this->prepareColumns($columns)})";

        foreach ($rowsets as $rowset) {
            if (count($statement) == 1) {
                $selectColumns = [];
                foreach ($columns as $column) {
                    $selectColumns[] = "? AS {$this->quote($column)}";
                }

                $statement[] = 'SELECT ' . implode(', ', $selectColumns);
            } else {
                //It is crityially important to use UNION ALL, UNION will try to merge values together
                //which will cause non predictable insert order
                $statement[] = 'UNION ALL SELECT ' . trim(str_repeat('?, ', count($columns)), ', ');
            }
        }

        return implode("\n", $statement);
    }

    /**
     * {@inheritdoc}
     *
     * @link http://stackoverflow.com/questions/10491492/sqllite-with-skip-offset-only-not-limit
     */
    protected function compileLimit(int $limit, int $offset): string
    {
        if (empty($limit) && empty($offset)) {
            return '';
        }

        $statement = '';

        if (!empty($limit) || !empty($offset)) {
            $statement = 'LIMIT ' . ($limit ?: '-1') . ' ';
        }

        if (!empty($offset)) {
            $statement .= "OFFSET {$offset}";
        }

        return trim($statement);
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

        if ($parameter->getType() == \PDO::PARAM_NULL) {
            switch ($operator) {
                case '=':
                    return 'IS';
                case '!=':
                    return 'IS NOT';
            }
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