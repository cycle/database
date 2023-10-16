<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Injection;

use Cycle\Database\Injection\JsonExpression;

abstract class PostgresJsonExpression extends JsonExpression
{
    /**
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function getField(string $statement): string
    {
        $path = \explode('->', $statement);

        return $this->quoter->quote(\array_shift($path));
    }


    /**
     * @param non-empty-string $statement
     *
     * @return array<non-empty-string>
     */
    protected function getWrappedPath(string $statement, string $quote = "'"): array
    {
        $path = \explode('->', $statement);
        \array_shift($path); // remove field name (first element)

        $result = [];
        foreach ($path as $pathAttribute) {
            $parsedAttributes = $this->parseJsonPathArrayKeys($pathAttribute);
            foreach ($parsedAttributes as $attribute) {
                $result[] = \filter_var($attribute, FILTER_VALIDATE_INT) !== false
                    ? $attribute
                    : $quote . $attribute . $quote;
            }
        }

        return $result;
    }

    /**
     * @param non-empty-string $attribute
     *
     * @return array<non-empty-string>
     */
    private function parseJsonPathArrayKeys(string $attribute): array
    {
        if (\preg_match('/(\[[^\]]+\])+$/', $attribute, $parts)) {
            $key = \substr($attribute, 0, \strpos($attribute, $parts[0]));

            \preg_match_all('/\[([^\]]+)\]/', $parts[0], $matches);
            $keys = $matches[1];

            $cleanKeys = \array_values(\array_filter($keys, static fn ($key) => $key !== ''));

            return \array_merge([$key], $cleanKeys);
        }

        return [$attribute];
    }
}
