<?php

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer;

use Cycle\Database\Driver\CursorOptions;

/**
 * SQL Server-specific cursor options.
 *
 * Maps to clauses of T-SQL `DECLARE … CURSOR …` syntax.
 */
final class SQLServerCursorOptions extends CursorOptions
{
    /**
     * @param CursorType $type Cursor flavor (snapshot semantics — see {@see CursorType}).
     *        The default {@see CursorType::Static} fulfills the snapshot-consistency
     *        contract of {@see \Cycle\Database\Driver\CursorableInterface}; other modes
     *        trade snapshot guarantees for different visibility semantics.
     */
    public function __construct(
        public readonly CursorType $type = CursorType::Static,
    ) {
        parent::__construct();
    }

    public static function from(CursorOptions $options): self
    {
        return $options instanceof self ? $options : new self();
    }
}
