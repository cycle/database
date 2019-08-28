<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Exception\InterpolatorException;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;

/**
 * Simple helper class used to interpolate query with given values. To be used for profiling and
 * debug purposes only, unsafe SQL are generated!
 */
final class Interpolator
{
    /**
     * Helper method used to interpolate SQL query with set of parameters, must be used only for
     * development purposes and never for real query!
     *
     * @param string               $query
     * @param ParameterInterface[] $parameters Parameters to be binded into query. Named list are supported.
     * @return string
     */
    public static function interpolate(string $query, array $parameters = []): string
    {
        if (empty($parameters)) {
            return $query;
        }

        //Flattening
        $parameters = self::flattenParameters($parameters);

        //Let's prepare values so they looks better
        foreach ($parameters as $index => $parameter) {
            if ($parameter->getType() === \PDO::PARAM_NULL) {
                continue;
            }

            $value = self::resolveValue($parameter);

            if (is_numeric($index)) {
                $query = self::replaceOnce('?', $value, $query);
            } else {
                $query = str_replace($index, $value, $query);
            }
        }

        return $query;
    }

    /**
     * Prepare set of query builder/user parameters to be send to PDO. Must convert DateTime
     * instances into valid database timestamps and resolve values of ParameterInterface.
     *
     * Every value has to wrapped with parameter interface.
     *
     * @param array $parameters
     * @return ParameterInterface[]
     *
     * @throws InterpolatorException
     */
    public static function flattenParameters(array $parameters): array
    {
        $flatten = [];
        foreach ($parameters as $key => $parameter) {
            if (!$parameter instanceof ParameterInterface) {
                //Let's wrap value
                $parameter = new Parameter($parameter, Parameter::DETECT_TYPE);
            }

            if ($parameter->isArray()) {
                if (!is_numeric($key)) {
                    throw new InterpolatorException("Array parameters can not be named");
                }

                //Quick and dirty
                $flatten = array_merge($flatten, $parameter->flatten());
            } else {
                if (is_numeric($key)) {
                    //We have to shift numeric keys due arrays
                    $flatten[] = $parameter;
                } else {
                    $flatten[$key] = $parameter;
                }
            }
        }

        return $flatten;
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
                return $parameter ? 'true' : 'false';

            case 'integer':
                return strval($parameter + 0);

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

                if ($parameter instanceof \DateTime) {
                    //Let's process dates different way
                    return "'" . $parameter->format(\DateTime::ISO8601) . "'";
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
    private static function replaceOnce(string $search, string $replace, string $subject): string
    {
        $position = strpos($subject, $search);
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }
}