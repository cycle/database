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

        // When 'encode' is set to 'true', it must encode any value into a JSON string
        // even if it is already a valid JSON string independently of the 'validate' parameter
        yield [\json_encode('fr'), \json_encode(\json_encode('fr')), true, true];
        yield [\json_encode('fr'), \json_encode(\json_encode('fr')), true, false];

        // A valid JSON string with 'encode=false' under both enabled and disabled validation
        yield [\json_encode('fr'), \json_encode('fr'), false, true];
        yield [\json_encode('fr'), \json_encode('fr'), false, false];

        // Invalid JSON string without validation and encoding should be returned as is
        yield ['fr', 'fr', false, false];

        // Stringable object will be converted to string if 'encode' is set to 'false'
        yield [
            new class() implements \Stringable {
                public function __toString(): string
                {
                    return 'foo';
                }
            },
            'foo',
            false,
            false,
        ];

        // JsonSerializable object will be converted to JSON string correctly if 'encode' is set to 'true'
        yield [
            new class() implements \JsonSerializable {
                public function jsonSerialize(): array
                {
                    return ['foo' => 'bar'];
                }
            },
            '{"foo":"bar"}',
            true,
            false,
        ];
    }

    public static function toJsonInvalidValuesDataProvider(): \Traversable
    {
        yield ['fr', false, true];
        yield [\fopen(__FILE__, 'rb'), true, false];
        yield [\fopen(__FILE__, 'rb'), true, true];
    }
}
