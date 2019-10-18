<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Driver\QueryBindings;
use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Query\Traits\TokenTrait;
use Spiral\Database\Query\Traits\WhereTrait;

/**
 * Update statement builder.
 */
class UpdateQuery extends AbstractQuery
{
    use TokenTrait;
    use WhereTrait;

    public const QUERY_TYPE = Compiler::UPDATE_QUERY;

    /**
     * Every affect builder must be associated with specific table.
     *
     * @var string
     */
    protected $table = '';

    /**
     * Column names associated with their values.
     *
     * @var array
     */
    protected $values = [];

    /**
     * @param string|null $table
     * @param array       $where
     * @param array       $values
     */
    public function __construct(
        string $table = null,
        array $where = [],
        array $values = []
    ) {
        $this->table = $table ?? '';
        $this->values = $values;

        if (!empty($where)) {
            $this->where($where);
        }
    }

    /**
     * Change target table.
     *
     * @param string $table Table name without prefix.
     * @return self|$this
     */
    public function in(string $table): UpdateQuery
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Change value set to be updated, must be represented by array of columns associated with new
     * value to be set.
     *
     * @param array $values
     * @return self|$this
     */
    public function values(array $values): UpdateQuery
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Get list of columns associated with their values.
     *
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Set update value.
     *
     * @param string $column
     * @param mixed  $value
     * @return self|$this
     */
    public function set(string $column, $value): UpdateQuery
    {
        $this->values[$column] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(QueryBindings $bindings, CompilerInterface $compiler): string
    {
        if ($this->values === []) {
            throw new BuilderException('Update values must be specified');
        }

        return $compiler->compileUpdate($bindings, $this->table, $this->values, $this->whereTokens);
    }

    /**
     * {@inheritdoc}
     *
     * Affect queries will return count of affected rows.
     *
     * @return int
     */
    public function run(): int
    {
        if ($this->compiler === null) {
            throw new BuilderException('Unable to run query without assigned driver');
        }

        $bindings = new QueryBindings();
        $queryString = $this->compile($bindings, $this->compiler);

        return $this->driver->execute($queryString, $bindings->getParameters());
    }
}
