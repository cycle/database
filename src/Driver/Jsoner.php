<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Exception\DriverException;

/**
 * Helper that allows to convert any value to JSON.
 */
final class Jsoner
{
    /**
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Checking the value that it is valid JSON.
     *
     * @throws \JsonException
     */
    public static function toJson(mixed $value, bool $encode = true, bool $validate = true): string
    {
        if (!$encode && $validate && \is_string($value) && !json_validate($value)) {
            throw new DriverException('Invalid JSON value.');
        }

        if ($encode && !$validate) {
            $value = \json_encode($value, \JSON_UNESCAPED_UNICODE|\JSON_THROW_ON_ERROR);
        }

        return $encode && $validate && (!\is_string($value) || !json_validate($value))
            ? \json_encode($value, \JSON_UNESCAPED_UNICODE|\JSON_THROW_ON_ERROR)
            : $value;
    }
}
