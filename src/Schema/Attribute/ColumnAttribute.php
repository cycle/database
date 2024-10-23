<?php

declare(strict_types=1);

namespace Cycle\Database\Schema\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ColumnAttribute
{
    /**
     * @param non-empty-string[]|null $types List of column types that support this attribute.
     *        Empty list means all types.
     */
    public function __construct(
        public ?array $types = null,
    ) {}
}
