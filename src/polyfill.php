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
    \Spiral\Database\Driver\SQLServer\Schema\SQlServerForeignKey::class
);


spl_autoload_register(static function (string $class) {
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

// Preload some aliases
interface_exists(\Spiral\Database\Driver\CachingCompilerInterface::class);
interface_exists(\Spiral\Database\Driver\CompilerInterface::class);
interface_exists(\Spiral\Database\Driver\HandlerInterface::class);
interface_exists(\Spiral\Database\Driver\DriverInterface::class);
interface_exists(\Spiral\Database\Query\BuilderInterface::class);
interface_exists(\Spiral\Database\DatabaseInterface::class);

class_exists(\Spiral\Database\Exception\StatementException::class);
class_exists(\Spiral\Database\Config\DatabaseConfig::class);
class_exists(\Spiral\Database\Query\SelectQuery::class);
class_exists(\Spiral\Database\Query\InsertQuery::class);
class_exists(\Spiral\Database\Query\UpdateQuery::class);
class_exists(\Spiral\Database\Query\DeleteQuery::class);
class_exists(\Spiral\Database\Schema\State::class);
