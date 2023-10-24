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
 * @method $this andWhereJson(string $column, mixed $value)
 * @method $this orWhereJson(string $column, mixed $value)
 * @method $this whereJsonContains(string $column, mixed $value)
 * @method $this andWhereJsonContains(string $column, mixed $value)
 * @method $this orWhereJsonContains(string $column, mixed $value)
 * @method $this whereJsonDoesntContain(string $column, mixed $value)
 * @method $this andWhereJsonDoesntContain(string $column, mixed $value)
 * @method $this orWhereJsonDoesntContain(string $column, mixed $value)
 * @method $this whereJsonContainsKey(string $column)
 * @method $this andWhereJsonContainsKey(string $column)
 * @method $this orWhereJsonContainsKey(string $column)
 * @method $this whereJsonDoesntContainKey(string $column)
 * @method $this andWhereJsonDoesntContainKey(string $column)
 * @method $this orWhereJsonDoesntContainKey(string $column)
 * @method $this whereJsonLength(string $column, int $length, string $operator = '=')
 * @method $this andWhereJsonLength(string $column, int $length, string $operator = '=')
 * @method $this orWhereJsonLength(string $column, int $length, string $operator = '=')
 */
class DeleteQuery extends ActiveQuery
{
    use TokenTrait;
    use WhereTrait;

    protected string $table = '';

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
     * @psalm-param non-empty-string $into Table name without prefix.
     */
    public function from(string $into): self
    {
        $this->table = $into;

        return $this;
    }

    /**
     * Alias for execute method();
     */
    public function run(): int
    {
        $params = new QueryParameters();
        $queryString = $this->sqlStatement($params);

        return $this->driver->execute($queryString, $params->getParameters());
    }

    public function getType(): int
    {
        return CompilerInterface::DELETE_QUERY;
    }

    public function getTokens(): array
    {
        return [
            'table' => $this->table,
            'where' => $this->whereTokens,
        ];
    }
}
