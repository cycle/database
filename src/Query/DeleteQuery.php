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
class DeleteQuery extends AbstractQuery
{
    use TokenTrait;
    use WhereTrait;

    public const QUERY_TYPE = Compiler::DELETE_QUERY;

    /**
     * Every affect builder must be associated with specific table.
     *
     * @var string
     */
    protected $table = '';

    /**
     * @param string $table Associated table name.
     * @param array  $where Initial set of where rules specified as array.
     */
    public function __construct(string $table = null, array $where = [])
    {
        $this->table = $table ?? '';

        if ($where !== []) {
            $this->where($where);
        }
    }

    /**
     * Change target table.
     *
     * @param string $into Table name without prefix.
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
    public function compile(QueryBindings $bindings, CompilerInterface $compiler): string
    {
        return $compiler->compileDelete($bindings, $this->table, $this->whereTokens);
    }

    /**
     * Alias for execute method();
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
