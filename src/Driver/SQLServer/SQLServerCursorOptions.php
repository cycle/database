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
     *        contract of {@see \Cycle\Database\Driver\CursorInterface}; other modes
     *        trade snapshot guarantees for different visibility semantics.
     * @param string|null $name Optional cursor name. When null, the driver generates a random
     *        unique name per call (collision-free, suitable for reusing the same options DTO
     *        across multiple invocations). When set, the value is used verbatim — caller is
     *        responsible for uniqueness. Must be a valid T-SQL identifier
     *        (letter/underscore start, alphanumerics/underscores, ≤128 chars).
     */
    public function __construct(
        public readonly CursorType $type = CursorType::Static,
        public readonly ?string $name = null,
    ) {
        if ($name !== null && (!\preg_match('/^[a-zA-Z_][\w]*$/', $name) || \strlen($name) > 128)) {
            throw new \InvalidArgumentException(\sprintf(
                'Cursor name `%s` must be a valid T-SQL identifier: start with letter or underscore, '
                . 'contain only alphanumerics and underscores, and be ≤128 characters.',
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
