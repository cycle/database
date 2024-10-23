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

/**
 * Simple helper class used to interpolate query with given values. To be used for profiling and
 * debug purposes only.
 */
final class Interpolator
{
    private const DEFAULT_DATETIME_FORMAT = \DateTimeInterface::ATOM;
    private const DATETIME_WITH_MICROSECONDS_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * Injects parameters into statement. For debug purposes only.
     *
     * @param non-empty-string $query
     *
     * @return non-empty-string
     */
    public static function interpolate(string $query, iterable $parameters = [], array $options = []): string
    {
        if ($parameters === []) {
            return $query;
        }

        ['named' => $named, 'unnamed' => $unnamed] = self::categorizeParameters($parameters);

        return \preg_replace_callback(
            '/(?<dq>"(?:\\\\\"|[^"])*")|(?<sq>\'(?:\\\\\'|[^\'])*\')|(?<ph>\\?)|(?<named>:[a-z_\\d]+)/',
            static function ($match) use (&$named, &$unnamed, $options) {
                $key = match (true) {
                    isset($match['named']) && $match['named'] !== '' => \ltrim($match['named'], ':'),
                    isset($match['ph']) => $match['ph'],
                    default => null,
                };

                switch (true) {
                    case $key === '?':
                        if (\key($unnamed) === null) {
                            return $match[0];
                        }

                        $value = \current($unnamed);
                        \next($unnamed);
                        return self::resolveValue($value, $options);
                    case isset($named[$key]) || \array_key_exists($key, $named):
                        return self::resolveValue($named[$key], $options);
                    default:
                        return $match[0];
                }
            },
            $query,
        );
    }

    /**
     * Get parameter value.
     *
     * @psalm-return non-empty-string
     */
    public static function resolveValue(mixed $parameter, array $options): string
    {
        if ($parameter instanceof ParameterInterface) {
            return self::resolveValue($parameter->getValue(), $options);
        }

        /** @since PHP 8.1 */
        if ($parameter instanceof \BackedEnum) {
            $parameter = $parameter->value;
        }

        switch (\gettype($parameter)) {
            case 'boolean':
                return $parameter ? 'TRUE' : 'FALSE';

            case 'integer':
                return (string) $parameter;

            case 'NULL':
                return 'NULL';

            case 'double':
                return \sprintf('%F', $parameter);

            case 'string':
                return "'" . self::escapeStringValue($parameter, "'") . "'";

            case 'object':
                if ($parameter instanceof \Stringable) {
                    return "'" . self::escapeStringValue((string) $parameter, "'") . "'";
                }

                if ($parameter instanceof \DateTimeInterface) {
                    $format = $options['withDatetimeMicroseconds'] ?? false
                        ? self::DATETIME_WITH_MICROSECONDS_FORMAT
                        : self::DEFAULT_DATETIME_FORMAT;

                    return "'" . $parameter->format($format) . "'";
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
     * Categorizes parameters into named and unnamed.
     *
     * @param iterable $parameters Parameters to categorize.
     *
     * @return array{named: array<string, mixed>, unnamed: list<mixed>} An associative array with keys 'named' and 'unnamed'.
     */
    private static function categorizeParameters(iterable $parameters): array
    {
        $named = [];
        $unnamed = [];

        foreach ($parameters as $k => $v) {
            if (\is_int($k)) {
                $unnamed[] = $v;
            } else {
                $named[\ltrim($k, ':')] = $v;
            }
        }

        return ['named' => $named, 'unnamed' => $unnamed];
    }
}
