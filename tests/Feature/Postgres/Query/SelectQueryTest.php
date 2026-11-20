<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Query;

use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Tests\Feature\Common\Query\SelectQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

final class SelectQueryTest extends CommonClass
{
    #[Test]
    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {settings}->>'theme' = ?");
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

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR {settings}->>'theme' = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {settings}->'phone'->>'work' = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {settings}->'phones'->>1 = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {settings}->'phones'->1->'numbers'->>3 = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testSelectWithWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ({settings}->'languages')::jsonb @> ?");
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

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR ({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, [1, \json_encode('en')]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ({settings}->'phones'->'work')::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsSinglePath(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings', []);

        SqlAssert::same($select, 'SELECT * FROM {table} WHERE ({settings})::jsonb @> ?');
        SqlAssert::sameParameters($select, [\json_encode([])]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ({settings}->'phones'->1)::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT ({settings}->'languages')::jsonb @> ?");
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

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR NOT({settings}->'languages')::jsonb @>?");
        SqlAssert::sameParameters($select, [1, \json_encode('en')]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->'work')::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainSinglePath(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings', []);

        SqlAssert::same($select, 'SELECT * FROM {table} WHERE NOT ({settings})::jsonb @> ?');
        SqlAssert::sameParameters($select, [\json_encode([])]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->1)::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testSelectWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE coalesce(({settings}->'phones')::jsonb ?? 'work', false)");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE CASE WHEN jsonb_typeof(({settings}->'phones')::jsonb) = 'array'
                    THEN jsonb_array_length(({settings}->'phones')::jsonb) >= 2 ELSE false END");
    }

    #[Test]
    public function testSelectWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE CASE WHEN jsonb_typeof(({settings}->'phones'->1->'numbers')::jsonb) = 'array'
                    THEN jsonb_array_length(({settings}->'phones'->1->'numbers')::jsonb) >= 4 ELSE false END");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testSelectWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR NOT coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT coalesce(({settings}->'phones')::jsonb ?? 'work', false)");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT CASE WHEN jsonb_typeof(({settings}->'phones')::jsonb) = 'array'
                THEN jsonb_array_length(({settings}->'phones')::jsonb) >= 2 ELSE false END");
    }

    #[Test]
    public function testSelectWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE NOT CASE WHEN jsonb_typeof(({settings}->'phones'->1->'numbers')::jsonb) = 'array'
                THEN jsonb_array_length(({settings}->'phones'->1->'numbers')::jsonb) >= 4 ELSE false END");
    }

    #[Test]
    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'languages')::jsonb) >= ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? AND jsonb_array_length(({settings}->'languages')::jsonb) = ?");
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

        SqlAssert::same($select, "SELECT * FROM {table} WHERE {id} = ? OR jsonb_array_length(({settings}->'languages')::jsonb) = ?");
        SqlAssert::sameParameters($select, [1, 4]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'personal'->'languages')::jsonb) = ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'phones'->1)::jsonb) = ?");
        SqlAssert::sameParameters($select, [2]);
    }

    #[Test]
    public function testSelectWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'phones'->1->'numbers'->3)::jsonb) = ?");
        SqlAssert::sameParameters($select, [5]);
    }

    #[Test]
    #[DataProvider('orderByProvider')]
    public function testOrderBy(string|FragmentInterface $column, ?string $direction): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->orderBy($column, $direction);

        if (\is_string($column)) {
            $column = \sprintf('"%s"', $column);
        }

        SqlAssert::same($select, \sprintf('SELECT * FROM {table} ORDER BY %s %s', $column, $direction));
    }

    public function orderByProvider(): iterable
    {
        return [
            ['column', 'ASC'],
            ['column', 'DESC'],
            ['column', 'ASC NULLS LAST'],
            ['column', 'ASC NULLS FIRST'],
            ['column', 'DESC NULLS LAST'],
            ['column', 'DESC NULLS FIRST'],
            [new Fragment('RAND()'), null],
        ];
    }

    #[Test]
    public function testOrderByCompileException(): void
    {
        Expect::exception(\Cycle\Database\Exception\CompilerException::class)->withMessageContaining('Invalid sorting direction, only `ASC`, `ASC NULLS LAST`, `ASC NULLS FIRST`, `DESC`, `DESC NULLS LAST`, `DESC NULLS FIRST` are allowed');

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

        SqlAssert::same($select, "SELECT * FROM {table} ORDER BY {logs}->>'created_at' DESC");
    }
}
