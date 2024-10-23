<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use PDO;

/**
 * You can use the class to avoid the PDO for any reasons. For example, to create a PDO Mock in tests.
 *
 * @see PDO
 */
interface PDOInterface
{
    public function prepare(string $query, array $options = []): \PDOStatement|PDOStatementInterface|false;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;

    public function inTransaction(): bool;

    public function exec(string $statement): int|false;

    public function query(
        $statement,
        $mode = \PDO::ATTR_DEFAULT_FETCH_MODE,
        ...$fetch_mode_args,
    ): \PDOStatement|PDOStatementInterface|false;

    public function lastInsertId(?string $name = null): string|false;

    public function setAttribute(int $attribute, mixed $value): bool;

    public function getAttribute(int $attribute): mixed;

    public function errorCode(): ?string;

    public function errorInfo(): array;

    public function quote(string $string, int $type = \PDO::PARAM_STR): string|false;
}
