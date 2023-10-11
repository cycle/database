<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver\Postgres;

use Cycle\Database\Driver\CachingCompilerInterface;
use Cycle\Database\Driver\Compiler;
use Cycle\Database\Driver\Quoter;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\QueryParameters;

/**
 * Postgres syntax specific compiler.
 */
class PostgresCompiler extends Compiler implements CachingCompilerInterface
{
    /**
     * @psalm-return non-empty-string
     */
    protected function insertQuery(QueryParameters $params, Quoter $q, array $tokens): string
    {
        $result = parent::insertQuery($params, $q, $tokens);

        if ($tokens['return'] === null) {
            return $result;
        }

        return sprintf(
            '%s RETURNING %s',
            $result,
            $this->quoteIdentifier($tokens['return'])
        );
    }

    protected function distinct(QueryParameters $params, Quoter $q, string|bool|array $distinct): string
    {
        if ($distinct === false) {
            return '';
        }

        if (\is_array($distinct) && isset($distinct['on'])) {
            return sprintf('DISTINCT ON (%s)', $this->name($params, $q, $distinct['on']));
        }

        if (\is_string($distinct)) {
            return sprintf('DISTINCT (%s)', $this->name($params, $q, $distinct));
        }

        return 'DISTINCT';
    }

    protected function limit(QueryParameters $params, Quoter $q, int $limit = null, int $offset = null): string
    {
        if ($limit === null && $offset === null) {
            return '';
        }

        $statement = '';
        if ($limit !== null) {
            $statement = 'LIMIT ? ';
            $params->push(new Parameter($limit));
        }

        if ($offset !== null) {
            $statement .= 'OFFSET ?';
            $params->push(new Parameter($offset));
        }

        return trim($statement);
    }

    /**
     * @param non-empty-string $value
     *
     * @return non-empty-string
     */
    protected function wrapJsonSelector(string $value, Quoter $quoter): string
    {
        $path = \explode(self::JSON_DELIMITER, $value);
        $field = $quoter->quote(\array_shift($path));
        $wrappedPath = $this->wrapJsonPathAttributes($path);
        $attribute = \array_pop($wrappedPath);

        if (!empty($wrappedPath)) {
            return $field . self::JSON_DELIMITER . \implode(self::JSON_DELIMITER, $wrappedPath) . '->>' . $attribute;
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
