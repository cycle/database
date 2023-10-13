<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Query;

use Cycle\Database\Driver\SQLServer\Query\Traits\WhereJsonTrait;
use Cycle\Database\Query\UpdateQuery;

class SQLServerUpdateQuery extends UpdateQuery
{
    use WhereJsonTrait;
}
