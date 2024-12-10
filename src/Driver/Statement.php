<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use PDOStatement;
use Cycle\Database\StatementInterface;

/**
 * Adds few quick methods to PDOStatement and fully compatible with it. By default uses
 * PDO::FETCH_ASSOC mode.
 *
 * @internal Do not use this class directly.
 */
final class Statement implements StatementInterface, \IteratorAggregate
{
    public function __construct(
        private \PDOStatement|PDOStatementInterface $pdoStatement,
    ) {
        $this->pdoStatement->setFetchMode(self::FETCH_ASSOC);
    }

    public function getQueryString(): string
    {
        return $this->pdoStatement->queryString;
    }

    public function getPDOStatement(): \PDOStatement|PDOStatementInterface
    {
        return $this->pdoStatement;
    }

    public function fetch(int $mode = self::FETCH_ASSOC): mixed
    {
        return $this->pdoStatement->fetch($mode);
    }

    public function fetchColumn(?int $columnNumber = null): mixed
    {
        return $columnNumber === null
            ? $this->pdoStatement->fetchColumn()
            : $this->pdoStatement->fetchColumn($columnNumber);
    }

    public function fetchAll(int $mode = self::FETCH_ASSOC): array
    {
        return $this->pdoStatement->fetchAll($mode);
    }

    public function rowCount(): int
    {
        return $this->pdoStatement->rowCount();
    }

    public function columnCount(): int
    {
        return $this->pdoStatement->columnCount();
    }

    public function getIterator(): \Generator
    {
        foreach ($this->pdoStatement as $row) {
            yield $row;
        }

        $this->close();
    }

    public function close(): void
    {
        $this->pdoStatement->closeCursor();
    }
}
