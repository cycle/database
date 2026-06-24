<?php

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Query\Traits\TokenTrait;
use Cycle\Database\Query\Traits\WhereTrait;

/**
 * Minimal, driver-independent WHERE builder used to express the index-inference
 * predicate of {@see OnConflictWithPredicate::targetWhere()} with the same fluent
 * DSL as SelectQuery/UpdateQuery/DeleteQuery: operators, automatic null handling
 * (`!= null` → `IS NOT NULL`), AND/OR groups, `BETWEEN`, `IN`, etc.
 *
 * It reuses {@see WhereTrait} (the public where/andWhere/orWhere API) on top of
 * {@see TokenTrait} (the arg-to-token parser) — exactly the composition used by the
 * query classes — and produces a plain token array consumable by the compiler's
 * existing where-token renderer. It carries no driver or connection state, so it is
 * safe to build inside an immutable {@see OnConflict} value object.
 */
final class OnConflictWhere
{
    use TokenTrait;
    use WhereTrait;

    /**
     * Where tokens consumable by {@see \Cycle\Database\Driver\Compiler::where()}.
     */
    public function getWhereTokens(): array
    {
        return $this->whereTokens;
    }
}
