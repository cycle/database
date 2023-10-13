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
 * @method DeleteQuery whereJson(string $column, mixed $value)
 * @method DeleteQuery andWhereJson(string $column, mixed $value)
 * @method DeleteQuery orWhereJson(string $column, mixed $value)
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
