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

        $lastPosition = 0;
        $replaceOnce = static function (
            string $search,
            string $replace,
            string $subject
        ) use (&$lastPosition): string {
            $position = strpos($subject, $search, $lastPosition);
            if ($position !== false) {
                $subject = substr_replace($subject, $replace, $position, strlen($search));
                $lastPosition = $position + strlen($replace);
            }

            return $subject;
        };

        //Let's prepare values so they looks better
        foreach ($parameters as $index => $parameter) {
            $mask = is_numeric($index) ? ':' . ltrim($index, ':') : '?';

            $query = $replaceOnce($mask, self::resolveValue($parameter), $query);
        }

        return $query;
    }

    /**
     * Get parameter value.
     *
     * @psalm-return non-empty-string
     */
    protected static function resolveValue(mixed $parameter): string
    {
        if ($parameter instanceof ParameterInterface) {
            return self::resolveValue($parameter->getValue());
        }

        switch (gettype($parameter)) {
            case 'boolean':
                return $parameter ? 'TRUE' : 'FALSE';

            case 'integer':
                return (string)($parameter + 0);

            case 'NULL':
                return 'NULL';

            case 'double':
                return sprintf('%F', $parameter);

            case 'string':
                return "'" . addcslashes($parameter, "'") . "'";

            case 'object':
                if (method_exists($parameter, '__toString')) {
                    return "'" . addcslashes((string)$parameter, "'") . "'";
                }

                if ($parameter instanceof DateTimeInterface) {
                    return "'" . $parameter->format(DateTimeInterface::ATOM) . "'";
                }
        }

        return '[UNRESOLVED]';
    }
}
