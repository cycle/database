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
use Cycle\Database\Driver\SQLite\Injection\CompileJsonContainsKey;
use Cycle\Database\Driver\SQLite\Injection\CompileJsonDoesntContainKey;
use Cycle\Database\Driver\SQLite\Injection\CompileJsonLength;
use Cycle\Database\Exception\DriverException;

/**
 * @internal
 *
 * @psalm-internal Cycle\Database\Driver\SQLite
 */
trait WhereJsonTrait
{
    /**
     * @param non-empty-string $column
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
     */
    public function andWhereJson(string $column, mixed $value): self
    {
        return $this->whereJson($column, $value);
    }

    /**
     * @param non-empty-string $column
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
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Checking the value that it is valid JSON.
     */
    public function whereJsonContains(string $column, mixed $value, bool $encode = true, bool $validate = true): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Checking the value that it is valid JSON.
     */
    public function andWhereJsonContains(string $column, mixed $value, bool $encode = true, bool $validate = true): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Checking the value that it is valid JSON.
     */
    public function orWhereJsonContains(string $column, mixed $value, bool $encode = true, bool $validate = true): self
    {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Checking the value that it is valid JSON.
     */
    public function whereJsonDoesntContain(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true
    ): self {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Checking the value that it is valid JSON.
     */
    public function andWhereJsonDoesntContain(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true
    ): self {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Checking the value that it is valid JSON.
     */
    public function orWhereJsonDoesntContain(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true
    ): self {
        throw new DriverException('This database engine does not support JSON contains operations.');
    }

    /**
     * @param non-empty-string $column
     */
    public function whereJsonContainsKey(string $column): self
    {
        $this->registerToken(
            'AND',
            [new CompileJsonContainsKey($column)],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     */
    public function andWhereJsonContainsKey(string $column): self
    {
        return $this->whereJsonContainsKey($column);
    }

    /**
     * @param non-empty-string $column
     */
    public function orWhereJsonContainsKey(string $column): self
    {
        $this->registerToken(
            'OR',
            [new CompileJsonContainsKey($column)],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     */
    public function whereJsonDoesntContainKey(string $column): self
    {
        $this->registerToken(
            'AND',
            [new CompileJsonDoesntContainKey($column)],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     */
    public function andWhereJsonDoesntContainKey(string $column): self
    {
        return $this->whereJsonDoesntContainKey($column);
    }

    /**
     * @param non-empty-string $column
     */
    public function orWhereJsonDoesntContainKey(string $column): self
    {
        $this->registerToken(
            'OR',
            [new CompileJsonDoesntContainKey($column)],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param int<0, max> $length
     * @param non-empty-string $operator
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
     * @param int<0, max> $length
     * @param non-empty-string $operator
     */
    public function andWhereJsonLength(string $column, int $length, string $operator = '='): self
    {
        return $this->whereJsonLength($column, $length, $operator);
    }

    /**
     * @param non-empty-string $column
     * @param int<0, max> $length
     * @param non-empty-string $operator
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
