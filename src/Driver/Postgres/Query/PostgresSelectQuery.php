<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Query;

use Cycle\Database\Query\Enum\LockMode;
use Cycle\Database\Query\Enum\LockBehavior;
use Cycle\Database\Driver\Postgres\Query\Traits\WhereJsonTrait;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\SelectQuery;

class PostgresSelectQuery extends SelectQuery
{
    use WhereJsonTrait;

    /**
     * Apply distinct ON to the query.
     */
    public function distinctOn(FragmentInterface|string $distinctOn): SelectQuery
    {
        $this->distinct = ['on' => $distinctOn];

        return $this;
    }

    public function forShare(
        LockBehavior $behavior = LockBehavior::Wait,
        bool $keyOnly = false,
    ): self {
        $this->forUpdate = [
            'behavior' => $behavior,
            'mode' => $keyOnly === true ? LockMode::KeyShare : LockMode::Share,
        ];

        return $this;
    }

    public function forUpdate(
        LockBehavior $behavior = LockBehavior::Wait,
        bool $noKey = false,
    ): self {
        $this->forUpdate = [
            'behavior' => $behavior,
            'mode' => $noKey === true ? LockMode::NoKeyUpdate : LockMode::Update,
        ];

        return $this;
    }
}
