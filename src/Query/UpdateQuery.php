<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Query\Traits\TokenTrait;
use Spiral\Database\Query\Traits\WhereTrait;

/**
 * Update statement builder.
 */
class UpdateQuery extends ActiveQuery
{
    use TokenTrait;
    use WhereTrait;

    /** @var string */
    protected $table = '';

    /** @var array */
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

        if ($where !== []) {
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
     *
     * Affect queries will return count of affected rows.
     *
     * @return int
     */
    public function run(): int
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        return $this->driver->execute($queryString, $params->getParameters());
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return CompilerInterface::UPDATE_QUERY;
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return [
            'table'  => $this->table,
            'values' => $this->values,
            'where'  => $this->whereTokens
        ];
    }
}
