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
use Cycle\Database\Exception\BuilderException;

/**
 * @internal
 *
 * @psalm-internal Cycle\Database\Driver\MySQL
 */
trait WhereJsonTrait
{
    /**
     * @param non-empty-string $column
     * @param non-empty-string $method
     * @param array<non-empty-string, mixed> $params
     */
    protected function buildJsonInjection(
        string $column,
        mixed $value,
        string $method,
        array $params,
    ): array {
        return match ($method) {
            'whereJson', 'orWhereJson' => [new CompileJson($column), $value],
            'whereJsonContains', 'orWhereJsonContains' => [new CompileJsonContains(
                $column,
                Jsoner::toJson($value, $params['encode'], $params['validate']),
            )],
            'whereJsonDoesntContain', 'orWhereJsonDoesntContain' => [new CompileJsonDoesntContain(
                $column,
                Jsoner::toJson($value, $params['encode'], $params['validate']),
            )],
            'whereJsonContainsKey', 'orWhereJsonContainsKey' => [new CompileJsonContainsKey($column)],
            'whereJsonDoesntContainKey', 'orWhereJsonDoesntContainKey' => [new CompileJsonDoesntContainKey($column)],
            'whereJsonLength', 'orWhereJsonLength' => [new CompileJsonLength($column, $value, $params['operator'])],
            default => null,
        } ?? throw new BuilderException("This database engine can't handle the `$method` method.");
    }
}
