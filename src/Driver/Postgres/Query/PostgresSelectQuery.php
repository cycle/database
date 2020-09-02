<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver\Postgres\Query;

use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Query\SelectQuery;

class PostgresSelectQuery extends SelectQuery
{
    /**
     * Apply distinct ON to the query.
     *
     * @param string|FragmentInterface $distinctOn
     * @return self|$this
     */
    public function distinctOn($distinctOn): SelectQuery
    {
        $this->distinct = ['on' => $distinctOn];

        return $this;
    }
}
