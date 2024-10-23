<?php

/**
 * This file is part of Cycle Database package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Config\Support;

/**
 * @internal Cycle\Database\Config\Support\DataSourceName is an internal library class,
 * please do not use it in your code.
 *
 * @psalm-internal Cycle\Database\Config
 */
final class DataSourceName
{
    /**
     * @param non-empty-string $dsn
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    public static function normalize(string $dsn, string $name): string
    {
        if (!\str_starts_with($dsn, "$name:")) {
            $dsn = "$name:$dsn";
        }

        return $dsn;
    }

    /**
     * @param non-empty-string $haystack
     * @param array<non-empty-string>|non-empty-string $needle
     *
     * @return non-empty-string|null
     */
    public static function read(string $haystack, array|string $needle): ?string
    {
        $needle = \array_map(static fn(string $item): string => \preg_quote($item), (array) $needle);
        $pattern = \sprintf('/\b(?:%s)=([^;]+)/i', \implode('|', $needle));

        if (\preg_match($pattern, $haystack, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
