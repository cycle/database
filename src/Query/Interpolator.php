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
        //Let's prepare values so they looks better
        foreach ($parameters as $index => $parameter) {
            $mask = \is_numeric($index) ? '?' : ':' . \ltrim($index, ':');

            $query = self::replaceOnce($mask, self::resolveValue($parameter), $query, $lastPosition);
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

    /**
     * Replace search value only once.
     *
     * @psalm-param non-empty-string $search
     * @psalm-param non-empty-string $replace
     * @psalm-param non-empty-string $subject
     *
     * @psalm-return non-empty-string
     *
     * @see http://stackoverflow.com/questions/1252693/using-str-replace-so-that-it-only-acts-on-the-first-match
     */
    private static function replaceOnce(
        string $search,
        string $replace,
        string $subject,
        ?int &$caret,
    ): string {
        $position = \strpos($subject, $search, $caret);
        if ($position !== false) {
            $subject = \substr_replace($subject, $replace, $position, \strlen($search));
            $caret = $position + \strlen($replace);
        }

        return $subject;

        // $position = strpos($subject, $search);
        // if ($position !== false) {
        //     return substr_replace($subject, $replace, $position, strlen($search));
        // }
        //
        // return $subject;
    }
}
