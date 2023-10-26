<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\MySQL\Query\Traits;

use Cycle\Database\Driver\Jsoner;
use Cycle\Database\Driver\MySQL\Injection\CompileJson;
use Cycle\Database\Driver\MySQL\Injection\CompileJsonContains;
use Cycle\Database\Driver\MySQL\Injection\CompileJsonContainsKey;
use Cycle\Database\Driver\MySQL\Injection\CompileJsonDoesntContain;
use Cycle\Database\Driver\MySQL\Injection\CompileJsonDoesntContainKey;
use Cycle\Database\Driver\MySQL\Injection\CompileJsonLength;

/**
 * @internal
 *
 * @psalm-internal Cycle\Database\Driver\MySQL
 */
trait WhereJsonTrait
{
    /**
     * @param non-empty-string $column
     */
    public function whereJson(string $column, mixed $value): static
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
    public function orWhereJson(string $column, mixed $value): static
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
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false.
     */
    public function whereJsonContains(string $column, mixed $value, bool $encode = true, bool $validate = true): static
    {
        $this->registerToken(
            'AND',
            [new CompileJsonContains($column, Jsoner::toJson($value, $encode, $validate))],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false.
     */
    public function orWhereJsonContains(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true
    ): static {
        $this->registerToken(
            'OR',
            [new CompileJsonContains($column, Jsoner::toJson($value, $encode, $validate))],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false.
     */
    public function whereJsonDoesntContain(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true
    ): static {
        $this->registerToken(
            'AND',
            [new CompileJsonDoesntContain($column, Jsoner::toJson($value, $encode, $validate))],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false.
     */
    public function orWhereJsonDoesntContain(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true
    ): static {
        $this->registerToken(
            'OR',
            [new CompileJsonDoesntContain($column, Jsoner::toJson($value, $encode, $validate))],
            $this->whereTokens,
            $this->whereWrapper()
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     */
    public function whereJsonContainsKey(string $column): static
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
    public function orWhereJsonContainsKey(string $column): static
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
    public function whereJsonDoesntContainKey(string $column): static
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
    public function orWhereJsonDoesntContainKey(string $column): static
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
    public function whereJsonLength(string $column, int $length, string $operator = '='): static
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
    public function orWhereJsonLength(string $column, int $length, string $operator = '='): static
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
