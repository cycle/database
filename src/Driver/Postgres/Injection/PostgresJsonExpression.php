<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres\Injection;

use Cycle\Database\Exception\DriverException;
use Cycle\Database\Injection\JsonExpression;

abstract class PostgresJsonExpression extends JsonExpression
{
    /**
     * Returns the compiled quoted path without the field name and attribute.
     *
     * @param non-empty-string $statement
     *
     * @return non-empty-string
     */
    protected function getPath(string $statement, string $quote = "'"): string
    {
        $path = $this->getPathArray($statement, $quote);

        \array_pop($path);

        return \implode('->', $path);
    }

    /**
     * Returns the attribute (last part of the full path).
     *
     * @param non-empty-string $statement
     *
     * @return int|non-empty-string
     */
    protected function getAttribute(string $statement): string|int
    {
        $attribute = $this->findAttribute($statement);
        if ($attribute === null) {
            throw new DriverException('Invalid statement. Unable to extract attribute.');
        }

        return $attribute;
    }

    /**
     * Returns the attribute (last part of the full path). Returns null if the attribute is not found.
     *
     * @param non-empty-string $statement
     *
     * @return int|non-empty-string|null
     */
    protected function findAttribute(string $statement): string|int|null
    {
        $path = $this->getPathArray($statement);
        if ($path === []) {
            return null;
        }

        $attribute = \array_pop($path);

        return \filter_var($attribute, FILTER_VALIDATE_INT)
            ? (int) $attribute
            : $attribute;
    }

    /**
     * Returns array of the compiled quoted path and attribute without the field name.
     *
     * @param non-empty-string $statement
     *
     * @return array<non-empty-string>
     */
    private function getPathArray(string $statement, string $quote = "'"): array
    {
        $path = \explode('->', $statement);
        \array_shift($path); // remove field name (first element)

        $result = [];
        foreach ($path as $pathAttribute) {
            $parsedAttributes = $this->parseArraySyntax($pathAttribute);
            foreach ($parsedAttributes as $attribute) {
                $result[] = \filter_var($attribute, FILTER_VALIDATE_INT) !== false
                    ? $attribute
                    : $quote . $attribute . $quote;
            }
        }

        return $result;
    }
}
