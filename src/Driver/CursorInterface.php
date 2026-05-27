<?php

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Exception\DriverException;
use Cycle\Database\Exception\StatementException;
use Cycle\Database\StatementInterface;

/**
 * Implemented by drivers capable of opening a true server-side cursor over a
 * SELECT result set.
 *
 * Unlike a generic "lazy iterator" abstraction, a server-side cursor offers
 * **snapshot consistency** for the duration of its enclosing transaction:
 * rows inserted or modified after the cursor is opened do not appear in the
 * stream, regardless of how long iteration takes. This is the property that
 * distinguishes a cursor from a paginator or an unbuffered fetch — and the
 * reason this interface exposes the feature **without any fallback**.
 *
 * Drivers that cannot offer those guarantees must not implement this
 * interface. Callers that want a portable lazy iterator should build their
 * own (e.g. keyset pagination) on top of plain queries instead.
 */
interface CursorInterface extends DriverInterface
{
    /**
     * Open a server-side cursor for the given SELECT statement and yield rows
     * from it lazily.
     *
     * Implementations require an active transaction on the connection for the
     * cursor's lifetime; they must throw a {@see DriverException} when the
     * preconditions are not met. The cursor is released when the generator is
     * fully consumed, garbage-collected, or the consumer breaks out of the
     * iteration.
     *
     * @param iterable $parameters Query parameters (positional or named) bound to the SELECT.
     * @param CursorOptions $options Driver-specific cursor configuration. Drivers narrow the
     *        type internally via their own `from()` factory (e.g. {@see Postgres\PostgresCursorOptions::from()}).
     * @param int $mode Row representation mode, one of {@see StatementInterface}::FETCH_*.
     * @psalm-param non-empty-string $statement
     *
     * @return \Generator<int, array<array-key, mixed>>
     *
     * @throws DriverException
     * @throws StatementException
     */
    public function cursor(
        string $statement,
        iterable $parameters = [],
        CursorOptions $options = new CursorOptions(),
        int $mode = StatementInterface::FETCH_ASSOC,
    ): \Generator;
}
