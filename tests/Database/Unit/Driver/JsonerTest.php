<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver;

use Cycle\Database\Driver\Jsoner;
use PHPUnit\Framework\TestCase;

final class JsonerTest extends TestCase
{
    /**
     * @dataProvider toJsonValuesDataProvider
     */
    public function testToJson(mixed $value, mixed $expected, bool $encode, bool $validate): void
    {
        $this->assertSame($expected, Jsoner::toJson($value, $encode, $validate));
    }

    /**
     * @dataProvider toJsonInvalidValuesDataProvider
     */
    public function testToJsonException(mixed $value, bool $encode, bool $validate): void
    {
        $this->expectException(\Exception::class);
        Jsoner::toJson($value, $encode, $validate);
    }

    public static function toJsonValuesDataProvider(): \Traversable
    {
        // A non-encoded valid string with 'encode=true' is correctly encoded with both enabled and disabled validation
        yield ['fr', \json_encode('fr'), true, true];
        yield ['fr', \json_encode('fr'), true, false];

        // When 'encode' is set to 'true' and 'validate' is also set to 'true,'
        // validation processes must exclude the encoded value
        yield [\json_encode('fr'), \json_encode('fr'), true, true];

        // A valid JSON string with 'encode=false' under both enabled and disabled validation
        yield [\json_encode('fr'), \json_encode('fr'), false, true];
        yield [\json_encode('fr'), \json_encode('fr'), false, false];

        // A valid JSON string with 'encode=true' should not be encoded again when validation is enabled
        yield [\json_encode('fr'), \json_encode('fr'), true, true];
    }

    public static function toJsonInvalidValuesDataProvider(): \Traversable
    {
        yield ['fr', false, true];
        yield [\fopen(__FILE__, 'rb'), true, false];
        yield [\fopen(__FILE__, 'rb'), true, true];
    }
}
