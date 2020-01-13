<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Query;

use DateTimeInterface;
use Spiral\Database\Injection\ParameterInterface;

/**
 * Simple helper class used to interpolate query with given values. To be used for profiling and
 * debug purposes only.
 */
final class Interpolator
{
    /**
     * Injects parameters into statement. For debug purposes only.
     *
     * @param string   $query
     * @param iterable $parameters
     * @return string
     */
    public static function interpolate(string $query, iterable $parameters = []): string
    {
        if ($parameters === []) {
            return $query;
        }

        //Let's prepare values so they looks better
        foreach ($parameters as $index => $parameter) {
            if (!is_numeric($index)) {
                $query = str_replace(
                    [':' . $index, $index],
                    self::resolveValue($parameter),
                    $query
                );
                continue;
            }

            $query = self::replaceOnce(
                '?',
                self::resolveValue($parameter),
                $query
            );
        }

        return $query;
    }

    /**
     * Get parameter value.
     *
     * @param mixed $parameter
     * @return string
     */
    protected static function resolveValue($parameter): string
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
     * @see http://stackoverflow.com/questions/1252693/using-str-replace-so-that-it-only-acts-on-the-first-match
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    private static function replaceOnce(
        string $search,
        string $replace,
        string $subject
    ): string {
        $position = strpos($subject, $search);
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }
}
