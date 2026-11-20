<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\DeleteQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-mysql')]
class DeleteQueryTest extends CommonClass
{
    public const DRIVER = 'mysql';

    #[\Testo\Test]
    public function testDeleteWithWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select,
        );
        $this->assertSameParameters([1, 'dark'], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithOrWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select,
        );
        $this->assertSameParameters([1, 'dark'], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phone\".\"work\"')) = ?",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1]')) = ?",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]')) = ?",
            $select,
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND json_contains({settings}, ?, '$.\"languages\"')",
            $select,
        );
        $this->assertSameParameters([1, \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR json_contains({settings}, ?, '$.\"languages\"')",
            $select,
        );
        $this->assertSameParameters([1, \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\".\"work\"')",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContainsSinglePath(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings', []);

        $this->assertSameQuery('DELETE FROM {table} WHERE json_contains({settings}, ?)', $select);
        $this->assertSameParameters([\json_encode([])], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\"[1]')",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select,
        );
        $this->assertSameParameters([1, \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select,
        );
        $this->assertSameParameters([1, \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\".\"work\"')",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContainSinglePath(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings', []);

        $this->assertSameQuery('DELETE FROM {table} WHERE NOT json_contains({settings}, ?)', $select);
        $this->assertSameParameters([\json_encode([])], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1]')",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones->work');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\".\"work\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1]'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1].\"numbers\"[3]'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND NOT IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR NOT IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\".\"work\"'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1]'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1].\"numbers\"[3]'), 0)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"languages\"') >= ?",
            $select,
        );
        $this->assertSameParameters([1], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? AND json_length({settings}, '$.\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters([1, 3], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE {id} = ? OR json_length({settings}, '$.\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters([1, 4], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->personal->languages', 1);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"personal\".\"languages\"') = ?",
            $select,
        );
        $this->assertSameParameters([1], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]', 2);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"phones\"[1]') = ?",
            $select,
        );
        $this->assertSameParameters([2], $select);
    }

    #[\Testo\Test]
    public function testDeleteWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        $this->assertSameQuery(
            "DELETE FROM {table} WHERE json_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select,
        );
        $this->assertSameParameters([5], $select);
    }
}
