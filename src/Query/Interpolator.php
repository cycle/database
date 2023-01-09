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

        $named = [];
        $unnamed = [];

        foreach ($parameters as $k => $v) {
            if (\is_int($k)) {
                $unnamed[] = $v;
            } else {
                $named[\ltrim($k, ':')] = $v;
            }
        }

        return \preg_replace_callback(
            '/(?<dq>"(?:\\\\\"|[^"])*")|(?<sq>\'(?:\\\\\'|[^\'])*\')|(?<ph>\\?)|(?<named>:[a-z_\\d]+)/',
            static function ($match) use (&$named, &$unnamed) {
                if (isset($match['named']) && '' !== $match['named']) {
                    $key = \ltrim($match['named'], ':');
                } elseif (isset($match['ph'])) {
                    $key = $match['ph'];
                } else {
                    return $match[0];
                }

                if ('?' === $key) {
                    if (null === \key($unnamed)) {
                        return $match[0];
                    }

                    $value = \current($unnamed);
                    \next($unnamed);
                } elseif (isset($named[$key]) || \array_key_exists($key, $named)) {
                    $value = $named[$key];
                } else {
                    return $match[0];
                }

                return self::resolveValue($value);
            },
            $query
        );
    }

    /**
     * Get parameter value.
     *
     * @psalm-return non-empty-string
     */
    private static function resolveValue($parameter): string
    {
        if ($parameter instanceof ParameterInterface) {
            return self::resolveValue($parameter->getValue());
        }

        switch (\gettype($parameter)) {
            case 'boolean':
                return $parameter ? 'TRUE' : 'FALSE';

            case 'integer':
                return (string)$parameter;

            case 'NULL':
                return 'NULL';

            case 'double':
                return \sprintf('%F', $parameter);

            case 'string':
                return "'" . self::escapeStringValue($parameter) . "'";

            case 'object':
                if (\method_exists($parameter, '__toString')) {
                    return "'" . self::escapeStringValue((string)$parameter) . "'";
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
}
