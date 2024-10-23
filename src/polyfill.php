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
\class_alias(
    \Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey::class,
    \Spiral\Database\Driver\SQLServer\Schema\SQlServerForeignKey::class,
);

\spl_autoload_register(static function (string $class): void {
    if (\strpos($class, 'Spiral\\Database\\') === 0) {
        $original = 'Cycle\\Database\\' . \substr($class, 16);

        @\trigger_error(
            "$class has been deprecated since cycle/database 1.0 " .
            "and will be removed in further release. Please use class $original instead.",
            E_USER_DEPRECATED,
        );

        \class_alias($original, $class);
    }
});
