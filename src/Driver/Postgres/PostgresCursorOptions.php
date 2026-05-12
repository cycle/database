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
     * @param string|null $name Optional cursor name. When null, the driver generates a random
     *        unique name per call (collision-free, suitable for reusing the same options DTO
     *        across multiple invocations). When set, the value is used verbatim — caller is
     *        responsible for uniqueness. Must be a valid PostgreSQL identifier
     *        (letter/underscore start, alphanumerics/underscores, ≤63 chars).
     */
    public function __construct(
        public readonly int $chunkSize = 1000,
        public readonly bool $withHold = false,
        public readonly ?string $name = null,
    ) {
        if ($name !== null && (!\preg_match('/^[a-zA-Z_][\w]*$/', $name) || \strlen($name) > 63)) {
            throw new \InvalidArgumentException(\sprintf(
                'Cursor name `%s` must be a valid PostgreSQL identifier: start with letter or underscore, '
                . 'contain only alphanumerics and underscores, and be ≤63 characters.',
                $name,
            ));
        }

        parent::__construct();
    }

    public static function from(CursorOptions $options): self
    {
        return $options instanceof self ? $options : new self();
    }
}
