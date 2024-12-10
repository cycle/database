<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use JetBrains\PhpStorm\ArrayShape;
use PDO;
use PDOStatement;

/**
 * You can use the class to avoid the PDO for any reasons. For example, to create a PDO Mock in tests.
 *
 * @see PDOStatement
 */
interface PDOStatementInterface extends \IteratorAggregate
{
    public function execute(array|null $params = null): bool;

    public function fetch(
        int $mode = \PDO::FETCH_BOTH,
        int $cursorOrientation = \PDO::FETCH_ORI_NEXT,
        int $cursorOffset = 0,
    ): mixed;

    public function bindParam(
        int|string $param,
        mixed &$var,
        int $type = \PDO::PARAM_STR,
        ?int $maxLength = null,
        mixed $driverOptions = null,
    ): bool;

    public function bindColumn(
        int|string $column,
        mixed &$var,
        int $type = \PDO::PARAM_STR,
        ?int $maxLength = null,
        mixed $driverOptions = null,
    ): bool;

    public function bindValue(
        int|string $param,
        mixed $value,
        int $type = \PDO::PARAM_STR,
    ): bool;

    public function rowCount(): int;

    public function fetchColumn(int $column = 0): mixed;

    public function fetchAll(int $mode = \PDO::FETCH_BOTH, mixed ...$args): array;

    public function fetchObject(string|null $class = \stdClass::class, array $constructorArgs = []): object|false;

    public function errorCode(): ?string;

    #[ArrayShape([0 => 'string', 1 => 'int', 2 => 'string'])]
    public function errorInfo(): array;

    public function setAttribute(int $attribute, mixed $value): bool;

    public function getAttribute(int $name): mixed;

    public function columnCount(): int;

    public function getColumnMeta(int $column): array|false;

    public function setFetchMode($mode, $className = null, ...$params);

    public function nextRowset(): bool;

    public function closeCursor(): bool;

    public function debugDumpParams(): ?bool;
}
