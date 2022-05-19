<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Injection\ParameterInterface;
use DateTimeInterface;
use Stringable;

/**
 * Simple helper class used to interpolate query with given values. To be used for profiling and
 * debug purposes only.
 */
final class Interpolator
{
    /**
     * Injects parameters into statement. For debug purposes only.
     *
     * @psalm-param non-empty-string $query
     *
     * @psalm-return non-empty-string
     */
    public static function interpolate(string $query, iterable $parameters = []): string
    {
        if ($parameters === []) {
            return $query;
        }

        ['named' => $named, 'unnamed' => $unnamed] = self::normalizeParameters($parameters);
        $params = self::findParams($query);

        $caret = 0;
        $result = '';
        foreach ($params as $pos => $ph) {
            $result .= \substr($query, $caret, $pos - $caret);
            $caret = $pos + \strlen($ph);
            // find param
            $result .= match (true) {
                $ph === '?' && \count($unnamed) > 0 => self::resolveValue(\array_shift($unnamed)),
                \array_key_exists($ph, $named) => self::resolveValue($named[$ph]),
                default => $ph,
            };
        }
        $result .= \substr($query, $caret);

        return $result;
    }

    /**
     * Get parameter value.
     *
     * @psalm-return non-empty-string
     */
    private static function resolveValue(mixed $parameter): string
    {
        if ($parameter instanceof ParameterInterface) {
            return self::resolveValue($parameter->getValue());
        }

        switch (\gettype($parameter)) {
            case 'boolean':
                return $parameter ? 'TRUE' : 'FALSE';

            case 'integer':
                return (string)($parameter + 0);

            case 'NULL':
                return 'NULL';

            case 'double':
                return \sprintf('%F', $parameter);

            case 'string':
                return "'" . self::escapeStringValue($parameter, "'") . "'";

            case 'object':
                if ($parameter instanceof Stringable) {
                    return "'" . self::escapeStringValue((string)$parameter, "'") . "'";
                }

                if ($parameter instanceof DateTimeInterface) {
                    return "'" . $parameter->format(DateTimeInterface::ATOM) . "'";
                }
        }

        return '[UNRESOLVED]';
    }

    private static function escapeStringValue(string $value): string
    {
        return \strtr($value, [
            '\\%' => '\\%',
            '\\_' => '\\_',
            \chr(26) => '\\Z',
            \chr(0) => '\\0',
            "'" => "\\'",
            \chr(8) => '\\b',
            "\n" => '\\n',
            "\r" => '\\r',
            "\t" => '\\t',
            '\\' => '\\\\',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private static function findParams(string $query): array
    {
        \preg_match_all(
            '/(?<dq>"(?:\\\\\"|[^"])*")|(?<sq>\'(?:\\\\\'|[^\'])*\')|(?<ph>\\?)|(?<named>:[a-z_\\d]+)/',
            $query,
            $placeholders,
            PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL
        );
        $result = [];
        foreach ([...$placeholders['named'], ...$placeholders['ph']] as $tuple) {
            if ($tuple[0] === null) {
                continue;
            }
            $result[$tuple[1]] = $tuple[0];
        }
        \ksort($result);

        return $result;
    }

    /**
     * @return array{named: array, unnamed: array}
     */
    private static function normalizeParameters(iterable $parameters): array
    {
        $result = ['named' => [], 'unnamed' => []];
        foreach ($parameters as $k => $v) {
            if (\is_int($k)) {
                $result['unnamed'][$k] = $v;
            } else {
                $result['named'][':' . \ltrim($k, ':')] = $v;
            }
        }
        return $result;
    }
}
