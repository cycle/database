<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

use Spiral\Database\Injection\FragmentInterface as SpiralFragmentInterface;

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
\class_alias(FragmentInterface::class, SpiralFragmentInterface::class, false);
