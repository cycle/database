<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\SQLServer\Query\Traits;

use Cycle\Database\Driver\SQLServer\Injection\CompileJson;
use Cycle\Database\Driver\SQLServer\Injection\CompileJsonContains;
use Cycle\Database\Driver\SQLServer\Injection\CompileJsonContainsKey;
use Cycle\Database\Driver\SQLServer\Injection\CompileJsonDoesntContain;
use Cycle\Database\Driver\SQLServer\Injection\CompileJsonDoesntContainKey;
use Cycle\Database\Driver\SQLServer\Injection\CompileJsonLength;

/**
 * @internal
 *
 * @psalm-internal Cycle\Database\Driver\SQLServer
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
            $this->whereWrapper(),
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
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON. It is not used in this driver.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false. It is not used in this driver.
     */
    public function whereJsonContains(string $column, mixed $value, bool $encode = true, bool $validate = true): static
    {
        $this->registerToken(
            'AND',
            [new CompileJsonContains($column, $value)],
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON. It is not used in this driver.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false. It is not used in this driver.
     */
    public function orWhereJsonContains(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true,
    ): static {
        $this->registerToken(
            'OR',
            [new CompileJsonContains($column, $value)],
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON. It is not used in this driver.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false. It is not used in this driver.
     */
    public function whereJsonDoesntContain(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true,
    ): static {
        $this->registerToken(
            'AND',
            [new CompileJsonDoesntContain($column, $value)],
            $this->whereTokens,
            $this->whereWrapper(),
        );

        return $this;
    }

    /**
     * @param non-empty-string $column
     * @param bool $encode Encode the value into JSON. It is not used in this driver.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false. It is not used in this driver.
     */
    public function orWhereJsonDoesntContain(
        string $column,
        mixed $value,
        bool $encode = true,
        bool $validate = true,
    ): static {
        $this->registerToken(
            'OR',
            [new CompileJsonDoesntContain($column, $value)],
            $this->whereTokens,
            $this->whereWrapper(),
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
            $this->whereWrapper(),
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
            $this->whereWrapper(),
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
            $this->whereWrapper(),
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
            $this->whereWrapper(),
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
            $this->whereWrapper(),
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
            $this->whereWrapper(),
        );

        return $this;
    }
}
