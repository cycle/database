<?php

declare(strict_types=1);

namespace Cycle\Database\Driver;

/**
 * Cross-driver options for {@see CursorInterface::cursor()}.
 *
 * The base class is intentionally empty — it serves as a typed null-object for
 * drivers that have no extra knobs (SQLite) and as a marker for the
 * polymorphic parameter accepted by {@see CursorInterface::cursor()}.
 * Drivers that need additional configuration (FETCH FORWARD batch size,
 * snapshot type, WITH HOLD, etc.) extend this class with their own option
 * DTOs and narrow the generic instance via their `from()` factory.
 */
class CursorOptions
{
    public function __construct() {}
}
