<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

// Class name (letter case) bugfix.
// Replaces: SQlServerForeignKey to SQLServerForeignKey
class_alias(
    \Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey::class,
    \Spiral\Database\Driver\SQLServer\Schema\SQlServerForeignKey::class,
);

spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'Spiral\\Database\\') === 0) {
        $original = 'Cycle\\Database\\' . substr($class, 16);

        @trigger_error(
            "$class has been deprecated since cycle/database 1.0 " .
            "and will be removed in further release. Please use class $original instead.",
            E_USER_DEPRECATED
        );

        class_alias($original, $class);
    }
});

if (!function_exists('array_is_list')) {
    /**
     * Checks whether a given array is a list
     *
     * @param array $array
     *
     * @return bool Returns true if array is a list, false otherwise.
     */
    function array_is_list(array $array): bool
    {
        if ([] === $array) {
            return true;
        }

        $nextKey = -1;

        foreach ($array as $k => $v) {
            if ($k !== ++$nextKey) {
                return false;
            }
        }

        return true;
    }
}
