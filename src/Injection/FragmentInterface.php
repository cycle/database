<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

/**
 * Declares ability to be converted into sql statement.
 */
interface FragmentInterface
{
    /**
     * Return the fragment type.
     *
     */
    public function getType(): int;

    /**
     * Return the fragment tokens.
     *
     */
    public function getTokens(): array;
}
