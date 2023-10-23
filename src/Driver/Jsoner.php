<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

/**
 * Helper that allows to convert any value to JSON.
 *
 * @deprecated it's a draft
 */
final class Jsoner
{
    /**
     * @param bool $notEncodeValidJson Validate if $value is already JSON and return it as is.
     *
     * @throws \JsonException
     */
    public static function toJson(mixed $value, bool $notEncodeValidJson = true): string
    {
        return $notEncodeValidJson && \is_string($value) && \json_validate($value)
            ? $value
            : \json_encode($value, \JSON_UNESCAPED_UNICODE|\JSON_THROW_ON_ERROR);
    }
}
