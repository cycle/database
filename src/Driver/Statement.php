<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Generator;
use PDOStatement;
use Spiral\Database\StatementInterface;

/**
 * Adds few quick methods to PDOStatement and fully compatible with it. By default uses
 * PDO::FETCH_ASSOC mode.
 *
 * @internal Do not use this class directly.
 */
final class Statement implements StatementInterface, \IteratorAggregate
{
    /** @var PDOStatement */
    private $pdoStatement;

    /**
     * @param PDOStatement $pdoStatement
     */
    public function __construct(PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;
        $this->pdoStatement->setFetchMode(self::FETCH_ASSOC);
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->pdoStatement->queryString;
    }

    /**
     * @return PDOStatement
     */
    public function getPDOStatement(): PDOStatement
    {
        return $this->pdoStatement;
    }

    /**
     * @inheritDoc
     */
    public function fetch(int $mode = self::FETCH_ASSOC)
    {
        return $this->pdoStatement->fetch($mode);
    }

    /**
     * @inheritDoc
     */
    public function fetchColumn(int $columnNumber = null)
    {
        if ($columnNumber === null) {
            return $this->pdoStatement->fetchColumn();
        }

        return $this->pdoStatement->fetchColumn($columnNumber);
    }

    /**
     * @param int $mode
     * @return array
     */
    public function fetchAll(int $mode = self::FETCH_ASSOC): array
    {
        return $this->pdoStatement->fetchAll($mode);
    }

    /**
     * @return int
     */
    public function rowCount(): int
    {
        return $this->pdoStatement->rowCount();
    }

    /**
     * @return int
     */
    public function columnCount(): int
    {
        return $this->pdoStatement->columnCount();
    }

    /**
     * @return Generator
     */
    public function getIterator(): Generator
    {
        foreach ($this->pdoStatement as $row) {
            yield $row;
        }

        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->pdoStatement->closeCursor();
    }
}
