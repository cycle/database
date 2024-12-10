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
use Cycle\Database\Query\Traits\WhereJsonTrait;
use Cycle\Database\Query\Traits\WhereTrait;

/**
 * Update statement builder.
 */
class UpdateQuery extends ActiveQuery
{
    use TokenTrait;
    use WhereJsonTrait;
    use WhereTrait;

    protected string $table = '';

    public function __construct(
        ?string $table = null,
        array $where = [],
        protected array $values = [],
    ) {
        $this->table = $table ?? '';

        if ($where !== []) {
            $this->where($where);
        }
    }

    /**
     * Change target table.
     *
     * @psalm-param non-empty-string $table Table name without prefix.
     */
    public function in(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Change value set to be updated, must be represented by array of columns associated with new
     * value to be set.
     */
    public function values(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Set update value.
     *
     * @psalm-param non-empty-string $column
     */
    public function set(string $column, mixed $value): self
    {
        $this->values[$column] = $value;

        return $this;
    }

    /**
     * Affect queries will return count of affected rows.
     */
    public function run(): int
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        return $this->driver->execute($queryString, $params->getParameters());
    }

    public function getType(): int
    {
        return CompilerInterface::UPDATE_QUERY;
    }

    public function getTokens(): array
    {
        return [
            'table'  => $this->table,
            'values' => $this->values,
            'where'  => $this->whereTokens,
        ];
    }
}
