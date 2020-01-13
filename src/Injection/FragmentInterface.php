<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Injection;

/**
 * Declares ability to be converted into sql statement.
 */
interface FragmentInterface
{
    /**
     * Return the fragment type.
     *
     * @return int
     */
    public function getType(): int;

    /**
     * Return the fragment tokens.
     *
     * @return array
     */
    public function getTokens(): array;
}
