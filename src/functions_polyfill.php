<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

if (!\function_exists('array_is_list')) {
    /**
     * Checks whether a given array is a list
     *
     * @return bool Returns true if array is a list, false otherwise.
     */
    function array_is_list(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        $nextKey = -1;

        foreach ($array as $k => $_) {
            if ($k !== ++$nextKey) {
                return false;
            }
        }

        return true;
    }
}
