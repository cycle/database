<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Injection;

use Cycle\Database\Driver\Quoter;
use Cycle\Database\Injection\JsonExpression;

class CompileJson extends JsonExpression
{
    protected function compile(string $statement): string
    {
        $quoter = new Quoter('', '""');

        $path = \explode('->', $statement);
        $field = $quoter->quote(\array_shift($path));
        $wrappedPath = $this->wrapJsonPathAttributes($path);
        $attribute = \array_pop($wrappedPath);

        if (!empty($wrappedPath)) {
            return $field . '->' . \implode('->', $wrappedPath) . '->>' . $attribute;
        }

        return $field . '->>' . $attribute;
    }

    /**
     * @param array<non-empty-string> $path
     * @param non-empty-string $quote
     *
     * @return array<non-empty-string>
     */
    protected function wrapJsonPathAttributes(array $path, string $quote = "'"): array
    {
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
    protected function parseJsonPathArrayKeys(string $attribute): array
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
