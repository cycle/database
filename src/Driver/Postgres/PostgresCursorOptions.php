<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\CursorOptions;

/**
 * Postgres-specific cursor options.
 *
 * Maps to clauses of `DECLARE … CURSOR …` syntax.
 */
final class PostgresCursorOptions extends CursorOptions
{
    /**
     * @param int<1, max> $chunkSize Number of rows pulled by each `FETCH FORWARD N FROM cursor`
     *        round-trip. Higher values reduce network overhead at the cost of buffering
     *        a larger result chunk in client memory between yields.
     * @param bool $withHold When true, declares the cursor `WITH HOLD`. The cursor materializes
     *        the rest of its result on the server at `COMMIT` time and remains open for further
     *        `FETCH` calls outside the transaction. Use for long-running exports that should
     *        not keep a write-blocking transaction alive.
     */
    public function __construct(
        public readonly int $chunkSize = 1000,
        public readonly bool $withHold = false,
    ) {
        parent::__construct();
    }

    public static function from(CursorOptions $options): self
    {
        return $options instanceof self ? $options : new self();
    }
}
