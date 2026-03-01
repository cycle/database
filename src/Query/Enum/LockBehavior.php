<?php

declare(strict_types=1);

namespace Cycle\Database\Query\Enum;

/**
 * Lock wait behavior when row is already locked.
 */
enum LockBehavior
{
    /**
     * Default. Block until lock is released.
     *
     * Supports:
     * - MSSQL +
     * - MySQL +
     * - SQLITE -
     * - PostgreSQL +
     */
    case Wait;

    /**
     * Fail immediately if row is locked.
     *
     * Supports:
     * - MSSQL +
     * - MySQL +
     * - SQLITE -
     * - PostgreSQL +
     */
    case NoWait;

    /**
     * Skip locked rows, return only unlocked. Useful for job queues.
     *
     * Supports:
     * - MSSQL +
     * - MySQL +
     * - SQLITE -
     * - PostgreSQL +
     */
    case SkipLocked;
}
