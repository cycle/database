<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Oracle\Query;

use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\SelectQuery;

class OracleSelectQuery extends SelectQuery
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
