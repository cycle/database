<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Query\Traits\TokenTrait;
use Cycle\Database\Query\Traits\WhereTrait;
use Spiral\Database\Query\UpdateQuery as SpiralUpdateQuery;

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
     *
     * @return $this|self
     */
    public function in(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Change value set to be updated, must be represented by array of columns associated with new
     * value to be set.
     *
     * @param array $values
     *
     * @return $this|self
     */
    public function values(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Set update value.
     *
     * @param string $column
     * @param mixed  $value
     *
     * @return $this|self
     */
    public function set(string $column, $value): self
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
            'table' => $this->table,
            'values' => $this->values,
            'where' => $this->whereTokens,
        ];
    }
}
\class_alias(UpdateQuery::class, SpiralUpdateQuery::class, false);
