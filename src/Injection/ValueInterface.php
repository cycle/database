<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

use Spiral\Database\Injection\ValueInterface as SpiralValueInterface;

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
\class_alias(ValueInterface::class, SpiralValueInterface::class, false);
