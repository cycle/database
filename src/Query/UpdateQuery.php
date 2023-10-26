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

/**
 * Update statement builder.
 *
 * @method $this whereJson(string $column, mixed $value)
 * @method $this orWhereJson(string $column, mixed $value)
 * @method $this whereJsonContains(string $column, mixed $value, bool $encode = true, bool $validate = true)
 * @method $this orWhereJsonContains(string $column, mixed $value, bool $encode = true, bool $validate = true)
 * @method $this whereJsonDoesntContain(string $column, mixed $value, bool $encode = true, bool $validate = true)
 * @method $this orWhereJsonDoesntContain(string $column, mixed $value, bool $encode = true, bool $validate = true)
 * @method $this whereJsonContainsKey(string $column)
 * @method $this orWhereJsonContainsKey(string $column)
 * @method $this whereJsonDoesntContainKey(string $column)
 * @method $this orWhereJsonDoesntContainKey(string $column)
 * @method $this whereJsonLength(string $column, int $length, string $operator = '=')
 * @method $this orWhereJsonLength(string $column, int $length, string $operator = '=')
 */
class UpdateQuery extends ActiveQuery
{
    use TokenTrait;
    use WhereTrait;

    protected string $table = '';

    public function __construct(
        string $table = null,
        array $where = [],
        protected array $values = []
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
