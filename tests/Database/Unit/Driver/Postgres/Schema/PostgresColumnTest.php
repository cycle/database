<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Driver\Postgres\Schema;

use Cycle\Database\Driver\Postgres\Schema\PostgresColumn;
use PHPUnit\Framework\TestCase;

final class PostgresColumnTest extends TestCase
{
    /**
     * @dataProvider enumConstrainsDataProvider
     */
    public function testParseEnumValues(string $constrain, array $expected): void
    {
        $column = new PostgresColumn('', '');

        $ref = new \ReflectionMethod($column, 'parseEnumValues');
        $ref->setAccessible(true);

        $this->assertSame($expected, $ref->invoke($column, $constrain));
    }

    public static function enumConstrainsDataProvider(): \Traversable
    {
        // simple single value
        yield ["CHECK (((target)::text = 'catalog'::text))", ['catalog']];

        // single value with underscore
        yield ["CHECK (((type)::text = 'user_profile'::text))", ['user_profile']];

        // single value with number
        yield ["CHECK (((type)::text = 'user_profile_2015'::text))", ['user_profile_2015']];
        yield ["CHECK (((type)::text = 'user_profile2015'::text))", ['user_profile2015']];

        // single value in table with underscore
        yield ["CHECK (((log_type)::text = 'order'::text))", ['order']];

        // single value in table with number
        yield ["CHECK (((type_2013)::text = 'user_profile'::text))", ['user_profile']];
        yield ["CHECK (((type2)::text = 'catalog'::text))", ['catalog']];

        // simple multiple values
        yield ["CHECK (((target)::text = ANY (ARRAY['catalog'::text, 'view'::text])))", ['catalog', 'view']];

        // multiple values in table with underscore
        yield ["CHECK (((log_type)::text = ANY (ARRAY['catalog'::text, 'view'::text])))", ['catalog', 'view']];

        // multiple values in table with number
        yield ["CHECK (((log_type2)::text = ANY (ARRAY['catalog'::text, 'view'::text])))", ['catalog', 'view']];
        yield ["CHECK (((log_type_2)::text = ANY (ARRAY['catalog'::text, 'view'::text])))", ['catalog', 'view']];

        // multiple values with underscore
        yield ["CHECK (((type)::text = ANY (ARRAY['user_profile'::text, 'view'::text])))", ['user_profile', 'view']];

        // multiple values with number
        yield [
            "CHECK (((type)::text = ANY (ARRAY['user_profile_2'::text, 'view'::text])))",
            ['user_profile_2', 'view']
        ];
        yield [
            "CHECK (((type)::text = ANY (ARRAY['user_profile2'::text, 'view'::text])))",
            ['user_profile2', 'view']
        ];
    }
}
