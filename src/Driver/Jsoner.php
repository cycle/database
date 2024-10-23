<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Exception\BuilderException;

/**
 * Helper that can be used to convert values into JSON strings and validate them.
 */
final class Jsoner
{
    /**
     * @param bool $encode Encode the value into JSON.
     * @param bool $validate Check that $value is a valid JSON string if the $encode parameter is false.
     *
     * @throws BuilderException|\JsonException
     */
    public static function toJson(mixed $value, bool $encode = true, bool $validate = true): string
    {
        if ($encode) {
            return \json_encode($value, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
        }

        $result = (string) $value;

        if ($validate && !\json_validate($result)) {
            throw new BuilderException('Invalid JSON value.');
        }

        return $result;
    }
}
