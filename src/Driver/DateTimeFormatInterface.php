<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

/**
 * The methods of this interface should be part of Cycle\Database\Driver\DriverInterface,
 * but adding new methods to the interface will break backward compatibility in projects
 * that use their own driver implementations.
 *
 * The methods of this interface can be moved to the main driver interface when upgrading the project's major version.
 */
interface DateTimeFormatInterface
{
    /**
     * Returns DateTime format of the driver.
     */
    public function getDateTimeFormat(): string;
}
