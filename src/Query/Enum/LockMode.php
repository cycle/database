<?php

declare(strict_types=1);

namespace Cycle\Database\Query\Enum;

/**
 * Row-level lock strength.
 */
enum LockMode
{
    /**
     * Exclusive lock.
     *
     * Supports:
     *  - MSSQL +
     *  - MySQL +
     *  - SQLITE -
     *  - PostgreSQL +
     */
    case Update;

    /**
     * Shared lock.
     *
     * Supports:
     *   - MSSQL +
     *   - MySQL +
     *   - SQLITE -
     *   - PostgreSQL +
     */
    case Share;

    /**
     * Weakest lock - blocks only DELETE and PK/FK updates.
     *
     * Supports:
     *     - MSSQL - (Will be compiled as self::Share)
     *     - MySQL - (Will be compiled as self::Share)
     *     - SQLITE -
     *     - PostgreSQL +
     */
    case KeyShare;

    /**
     * Like LockMode::Update, but doesn't block PK/FK columns.
     * (Use when not modifying PK/FK columns)
     *
     * Supports:
     *    - MSSQL - (Will be compiled as self::Update)
     *    - MySQL - (Will be compiled as self::Update)
     *    - SQLITE -
     *    - PostgreSQL +
     */
    case NoKeyUpdate;
}
