<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\MySQL\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-mysql
 */
class SelectQueryTest extends CommonClass
{
    public const DRIVER = 'mysql';

    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        $this->assertSameQuery(
            'SELECT * FROM {users} LIMIT 18446744073709551615 OFFSET ?',
            $select
        );

        $this->assertSameParameters([20], $select);
    }

    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select
        );
        $this->assertSameParameters(['dark'], $select);
    }

    public function testSelectWithOrWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR json_unquote(json_extract({settings}, '$.\"theme\"')) = ?",
            $select
        );
        $this->assertSameParameters([1, 'dark'], $select);
    }

    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phone\".\"work\"')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1]')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]')) = ?",
            $select
        );
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([json_encode('en')], $select);
    }

    public function testSelectWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([1, json_encode('en')], $select);
    }

    public function testSelectWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\".\"work\"')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonContainsSinglePath(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings', []);

        $this->assertSameQuery('SELECT * FROM {table} WHERE json_contains({settings}, ?)', $select);
        $this->assertSameParameters([json_encode([])], $select);
    }

    public function testSelectWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\"[1]')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([json_encode('en')], $select);
    }

    public function testSelectWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR NOT json_contains({settings}, ?, '$.\"languages\"')",
            $select
        );
        $this->assertSameParameters([1, json_encode('en')], $select);
    }

    public function testSelectWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\".\"work\"')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonDoesntContainSinglePath(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings', []);

        $this->assertSameQuery('SELECT * FROM {table} WHERE NOT json_contains({settings}, ?)', $select);
        $this->assertSameParameters([json_encode([])], $select);
    }

    public function testSelectWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1]')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select
        );
    }

    public function testSelectWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR IFNULL(json_contains_path({settings}, 'one','$.\"languages\"'), 0)",
            $select
        );
    }

    public function testSelectWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones->work');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\".\"work\"'), 0)",
            $select
        );
    }

    public function testSelectWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1]'), 0)",
            $select
        );
    }

    public function testSelectWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1].\"numbers\"[3]'), 0)",
            $select
        );
    }

    public function testSelectWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND NOT IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select
        );
    }

    public function testSelectWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR NOT IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)",
            $select
        );
    }

    public function testSelectWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\".\"work\"'), 0)",
            $select
        );
    }

    public function testSelectWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1]'), 0)",
            $select
        );
    }

    public function testSelectWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1].\"numbers\"[3]'), 0)",
            $select
        );
    }

    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_length({settings}, '$.\"languages\"') >= ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testSelectWithWhereJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND json_length({settings}, '$.\"languages\"') = ?",
            $select
        );
        $this->assertSameParameters([1, 3], $select);
    }

    public function testSelectWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR json_length({settings}, '$.\"languages\"') = ?",
            $select
        );
        $this->assertSameParameters([1, 4], $select);
    }

    public function testSelectWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->personal->languages', 1);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_length({settings}, '$.\"personal\".\"languages\"') = ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testSelectWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]', 2);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_length({settings}, '$.\"phones\"[1]') = ?",
            $select
        );
        $this->assertSameParameters([2], $select);
    }

    public function testSelectWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE json_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?",
            $select
        );
        $this->assertSameParameters([5], $select);
    }
}
