<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Query;

use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\SelectQuery;
use Spiral\Database\Driver\Postgres\Query\PostgresSelectQuery as SpiralPostgresSelectQuery;

class PostgresSelectQuery extends SelectQuery
{
    /**
     * Apply distinct ON to the query.
     *
     * @param FragmentInterface|string $distinctOn
     *
     * @return $this|self
     */
    public function distinctOn($distinctOn): SelectQuery
    {
        $this->distinct = ['on' => $distinctOn];

        return $this;
    }
}
\class_alias(PostgresSelectQuery::class, SpiralPostgresSelectQuery::class, false);
