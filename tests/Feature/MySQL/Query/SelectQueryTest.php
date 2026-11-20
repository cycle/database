<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\MySQL\Query;

use Cycle\Database\Tests\Feature\Common\Query\SelectQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Expect;
use Testo\Test;

final class SelectQueryTest extends CommonClass
{
    #[Test]
    public function testOffsetNoLimit(): void
    {
        $select = $this->database->select()->from(['users'])->offset(20);

        SqlAssert::same($select, 'SELECT * FROM {users} LIMIT 18446744073709551615 OFFSET ?');

        SqlAssert::sameParameters($select, [20]);
    }

    #[Test]
    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"theme\"')) = ?");
        SqlAssert::sameParameters($select, ['dark']);
    }

    #[Test]
    public function testSelectWithOrWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR json_unquote(json_extract({settings}, '$.\"theme\"')) = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phone\".\"work\"')) = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1]')) = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_unquote(json_extract({settings}, '$.\"phones\"[1].\"numbers\"[3]')) = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_contains({settings}, ?, '$.\"languages\"')");
        SqlAssert::sameParameters($select, [\json_encode('en')]);
    }

    #[Test]
    public function testSelectWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR json_contains({settings}, ?, '$.\"languages\"')");
        SqlAssert::sameParameters($select, [1, \json_encode('en')]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\".\"work\"')");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsSinglePath(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings', []);

        SqlAssert::same($select, 'SELECT * FROM {table} WHERE json_contains({settings}, ?)');
        SqlAssert::sameParameters($select, [\json_encode([])]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\"[1]')");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"languages\"')");
        SqlAssert::sameParameters($select, [\json_encode('en')]);
    }

    #[Test]
    public function testSelectWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR NOT json_contains({settings}, ?, '$.\"languages\"')");
        SqlAssert::sameParameters($select, [1, \json_encode('en')]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\".\"work\"')");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainSinglePath(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings', []);

        SqlAssert::same($select, 'SELECT * FROM {table} WHERE NOT json_contains({settings}, ?)');
        SqlAssert::sameParameters($select, [\json_encode([])]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1]')");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT json_contains({settings}, ?, '$.\"phones\"[1].\"numbers\"[3]')");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)");
    }

    #[Test]
    public function testSelectWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR IFNULL(json_contains_path({settings}, 'one','$.\"languages\"'), 0)");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\".\"work\"'), 0)");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1]'), 0)");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1].\"numbers\"[3]'), 0)");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND NOT IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)");
    }

    #[Test]
    public function testSelectWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR NOT IFNULL(json_contains_path({settings}, 'one', '$.\"languages\"'), 0)");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\".\"work\"'), 0)");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1]'), 0)");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT IFNULL(json_contains_path({settings}, 'one', '$.\"phones\"[1].\"numbers\"[3]'), 0)");
    }

    #[Test]
    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_length({settings}, '$.\"languages\"') >= ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithWhereJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND json_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1, 3]);
    }

    #[Test]
    public function testSelectWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR json_length({settings}, '$.\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1, 4]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_length({settings}, '$.\"personal\".\"languages\"') = ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_length({settings}, '$.\"phones\"[1]') = ?");
        SqlAssert::sameParameters($select, [2]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE json_length({settings}, '$.\"phones\"[1].\"numbers\"[3]') = ?");
        SqlAssert::sameParameters($select, [5]);
    }

    #[Test]
    public function testOrderByCompileException(): void
    {
        Expect::exception(\Cycle\Database\Exception\CompilerException::class)->withMessageContaining('Invalid sorting direction, only `ASC`, `DESC` are allowed');

        $this->database
            ->select()
            ->from('table')
            ->orderBy('name', 'FOO')
            ->sqlStatement();
    }

    #[Test]
    public function testOrderByJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->orderBy('logs->created_at', 'DESC');

        SqlAssert::same($select, "SELECT * FROM {table} ORDER BY json_unquote(json_extract({logs}, '$.\"created_at\"')) DESC");
    }
}
