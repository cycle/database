<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Injection;

interface ValueInterface
{
    /**
     * Return value to be stored in database in raw form.
     *
     * @return string
     */
    public function rawValue(): string;

    /**
     * Return associated PDO type.
     *
     * @return int
     */
    public function rawType(): int;
}
