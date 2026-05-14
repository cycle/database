<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer;

/**
 * Type of SQL Server server-side cursor.
 *
 * Controls what the cursor sees over its lifetime versus the underlying table state.
 * Only {@see self::Static} fulfills the snapshot-consistency contract of
 * {@see \Cycle\Database\Driver\CursorInterface}; other modes are exposed for
 * advanced use cases that accept different consistency trade-offs.
 */
enum CursorType: string
{
    /**
     * `STATIC` — SQL Server copies the result set into `tempdb` at `OPEN` time;
     * the cursor reads from that snapshot and is unaffected by concurrent
     * INSERTs / UPDATEs / DELETEs.
     */
    case Static = 'STATIC';

    /**
     * `KEYSET` — keys are frozen at `OPEN` time, but column values are
     * re-read on each `FETCH`. Updates to existing rows ARE visible; inserts
     * are not. Deleted rows show up as missing.
     *
     * Not snapshot-consistent in the strict sense.
     */
    case Keyset = 'KEYSET';

    /**
     * `DYNAMIC` — the result set is fully re-evaluated on every `FETCH`. Inserts,
     * updates, and deletes by other transactions are all visible. No snapshot.
     */
    case Dynamic = 'DYNAMIC';

    /**
     * `FAST_FORWARD` — performance-optimized forward-only read-only cursor. Implemented
     * as a dynamic cursor under the hood, so it offers no snapshot. Useful only when
     * you trade consistency for throughput.
     */
    case FastForward = 'FAST_FORWARD';
}
