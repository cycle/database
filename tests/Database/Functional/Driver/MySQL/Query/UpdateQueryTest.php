<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpdateQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class UpdateQueryTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testUpdateWithWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select,
        );
        $this->assertSameParameters(['value', 'dark'], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithOrWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select,
        );
        $this->assertSameParameters(['value', 1, 'dark'], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_unquote(json_extract({settings}, '$.\"phone\".\"work\"')) = ?",
            $select,
        );
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1]')) = ?",
            $select,
        );
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]')) = ?",
            $select,
        );
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContains(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_contains({settings}, ?, '$.\"languages\"')",
            $select,
        );
        $this->assertSameParameters(['value', \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR json_contains({settings}, ?, '$.\"languages\"')",
            $select,
        );
        $this->assertSameParameters(['value', 1, \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_contains({settings}, ?, '$.\"phones\".\"work\"')",
            $select,
        );
        $this->assertSameParameters(['value', \json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContainsSinglePath(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings', []);

        $this->assertSameQuery('UPDATE {table} SET {some} = ? WHERE json_contains({settings}, ?)', $select);
        $this->assertSameParameters(['value', \json_encode([])], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_contains({settings}, ?, '$.\"phones\"[1]')",
            $select,
        );
        $this->assertSameParameters(['value', \json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')",
            $select,
        );
        $this->assertSameParameters(['value', \json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select,
        );
        $this->assertSameParameters(['value', \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select,
        );
        $this->assertSameParameters(['value', 1, \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT json_contains({settings}, ?, '$.\"phones\".\"work\"')",
            $select,
        );
        $this->assertSameParameters(['value', \json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContainSinglePath(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings', []);

        $this->assertSameQuery('UPDATE {table} SET {some} = ? WHERE NOT json_contains({settings}, ?)', $select);
        $this->assertSameParameters(['value', \json_encode([])], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1]')",
            $select,
        );
        $this->assertSameParameters(['value', \json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')",
            $select,
        );
        $this->assertSameParameters(['value', \json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones->work');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\".\"work\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones[1]');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1]'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1].\"numbers\"[3]'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? AND NOT IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR NOT IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones->work');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\".\"work\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones[1]');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1]'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1].\"numbers\"[3]'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_length({settings}, '$.\"languages\"') >= ?",
            $select,
        );
        $this->assertSameParameters(['value', 1], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? AND json_length({settings}, '$.\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters(['value', 1, 3], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR json_length({settings}, '$.\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters(['value', 1, 4], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->personal->languages', 1);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_length({settings}, '$.\"personal\".\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters(['value', 1], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->phones[1]', 2);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_length({settings}, '$.\"phones\"[1]') = ?",
            $select,
        );
        $this->assertSameParameters(['value', 2], $select);
    }

    #[\Testo\Test]
    public function testUpdateWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE json_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select,
        );
        $this->assertSameParameters(['value', 5], $select);
    }
}
