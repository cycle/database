<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Query;

use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\SelectQuery;

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
