<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLite\Query\Traits;

use Cycle\Database\Driver\SQLite\Injection\CompileJson;
use Cycle\Database\Driver\SQLite\Injection\CompileJsonLength;
use Cycle\Database\Exception\DriverException;

/**
 * @internal
 */
trait WhereJsonTrait
{
    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function whereJson(string $column, mixed $value): self
    {
        $this->registerToken(
            'AND',
            [new CompileJson($column), $value],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function andWhereJson(string $column, mixed $value): self
    {
        $this->registerToken(
            'AND',
            [new CompileJson($column), $value],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function orWhereJson(string $column, mixed $value): self
    {
        $this->registerToken(
            'OR',
            [new CompileJson($column), $value],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function whereJsonContains(string $column, mixed $value): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function andWhereJsonContains(string $column, mixed $value): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function orWhereJsonContains(string $column, mixed $value): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function whereJsonDoesntContain(string $column, mixed $value): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function andWhereJsonDoesntContain(string $column, mixed $value): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     *
     * @return $this|self
     */
    public function orWhereJsonDoesntContain(string $column, mixed $value): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     * @param 0|positive-int $length
     * @param non-empty-string $operator
     *
     * @return $this|self
     */
    public function whereJsonLength(string $column, int $length, string $operator = '='): self
    {
        $this->registerToken(
            'AND',
            [new CompileJsonLength($column, $length, $operator)],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param 0|positive-int $length
     * @param non-empty-string $operator
     *
     * @return $this|self
     */
    public function andWhereJsonLength(string $column, int $length, string $operator = '='): self
    {
        return $this->whereJsonLength($column, $length, $operator);
    }

    /**
     * @param non-empty-string $column
     * @param 0|positive-int $length
     * @param non-empty-string $operator
     *
     * @return $this|self
     */
    public function orWhereJsonLength(string $column, int $length, string $operator = '='): self
    {
        $this->registerToken(
            'OR',
            [new CompileJsonLength($column, $length, $operator)],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }
}