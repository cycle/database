<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Query;

use Spiral\Database\Driver\Compiler;
use Spiral\Database\Driver\Driver;
use Spiral\Database\Query\Traits\TokenTrait;
use Spiral\Database\Query\Traits\WhereTrait;


/**
 * Update statement builder.
 */
class DeleteQuery extends QueryBuilder
{
    use TokenTrait, WhereTrait;

    const QUERY_TYPE = Compiler::DELETE_QUERY;

    /**
     * Every affect builder must be associated with specific table.
     *
     * @var string
     */
    protected $table = '';

    /**
     * {@inheritdoc}
     *
     * @param string $table Associated table name.
     * @param array  $where Initial set of where rules specified as array.
     */
    public function __construct(Driver $driver, Compiler $compiler, string $table = '', array $where = [])
    {
        parent::__construct($driver, $compiler);
        $this->table = $table;

        if (!empty($where)) {
            $this->where($where);
        }
    }

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
    public function sqlStatement(Compiler $quoter = null): string
    {
        if (empty($quoter)) {
            $quoter = $this->compiler->resetQuoter();
        }

        return $quoter->compileDelete($this->table, $this->whereTokens);
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
        return $this->pdoStatement()->rowCount();
    }
}
