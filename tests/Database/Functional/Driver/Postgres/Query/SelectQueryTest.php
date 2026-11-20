<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

#[\Testo\Filter\Group('driver', 'driver-postgres')]
class SelectQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';

    #[\Testo\Test]
    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->>'theme' = ?", $select);
        $this->assertSameParameters(['dark'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {id} = ? OR {settings}->>'theme' = ?", $select);
        $this->assertSameParameters([1, 'dark'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phone'->>'work' = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phones'->>1 = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phones'->1->'numbers'->>3 = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ({settings}->'languages')::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([\json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR ({settings}->'languages')::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([1, \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ({settings}->'phones'->'work')::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsSinglePath(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings', []);

        $this->assertSameQuery('SELECT * FROM {table} WHERE ({settings})::jsonb @> ?', $select);
        $this->assertSameParameters([\json_encode([])], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ({settings}->'phones'->1)::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT ({settings}->'languages')::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([\json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR NOT({settings}->'languages')::jsonb @>?",
            $select,
        );
        $this->assertSameParameters([1, \json_encode('en')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->'work')::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainSinglePath(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings', []);

        $this->assertSameQuery('SELECT * FROM {table} WHERE NOT ({settings})::jsonb @> ?', $select);
        $this->assertSameParameters([\json_encode([])], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->1)::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?",
            $select,
        );
        $this->assertSameParameters([\json_encode('+1234567890')], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE coalesce(({settings})::jsonb ?? 'languages', false)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR coalesce(({settings})::jsonb ?? 'languages', false)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones->work');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE coalesce(({settings}->'phones')::jsonb ?? 'work', false)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE CASE WHEN jsonb_typeof(({settings}->'phones')::jsonb) = 'array'
                    THEN jsonb_array_length(({settings}->'phones')::jsonb) >= 2 ELSE false END",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE CASE WHEN jsonb_typeof(({settings}->'phones'->1->'numbers')::jsonb) = 'array'
                    THEN jsonb_array_length(({settings}->'phones'->1->'numbers')::jsonb) >= 4 ELSE false END",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT coalesce(({settings})::jsonb ?? 'languages', false)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR NOT coalesce(({settings})::jsonb ?? 'languages', false)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT coalesce(({settings}->'phones')::jsonb ?? 'work', false)",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT CASE WHEN jsonb_typeof(({settings}->'phones')::jsonb) = 'array'
                THEN jsonb_array_length(({settings}->'phones')::jsonb) >= 2 ELSE false END",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT CASE WHEN jsonb_typeof(({settings}->'phones'->1->'numbers')::jsonb) = 'array'
                THEN jsonb_array_length(({settings}->'phones'->1->'numbers')::jsonb) >= 4 ELSE false END",
            $select,
        );
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'languages')::jsonb) >= ?",
            $select,
        );
        $this->assertSameParameters([1], $select);
    }

    #[\Testo\Test]
    public function testSelectWithJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND jsonb_array_length(({settings}->'languages')::jsonb) = ?",
            $select,
        );
        $this->assertSameParameters([1, 3], $select);
    }

    #[\Testo\Test]
    public function testSelectWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR jsonb_array_length(({settings}->'languages')::jsonb) = ?",
            $select,
        );
        $this->assertSameParameters([1, 4], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->personal->languages', 1);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'personal'->'languages')::jsonb) = ?",
            $select,
        );
        $this->assertSameParameters([1], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]', 2);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'phones'->1)::jsonb) = ?",
            $select,
        );
        $this->assertSameParameters([2], $select);
    }

    #[\Testo\Test]
    public function testSelectWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'phones'->1->'numbers'->3)::jsonb) = ?",
            $select,
        );
        $this->assertSameParameters([5], $select);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('orderByProvider')]
    public function testOrderBy(string|FragmentInterface $column, ?string $direction): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->orderBy($column, $direction);

        if (\is_string($column)) {
            $column = \sprintf('"%s"', $column);
        }

        $this->assertSameQuery(
            \sprintf('SELECT * FROM {table} ORDER BY %s %s', $column, $direction),
            $select,
        );
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

    #[\Testo\Test]
    public function testOrderByCompileException(): void
    {
        \Testo\Expect::exception(\Cycle\Database\Exception\CompilerException::class)->withMessageContaining('Invalid sorting direction, only `ASC`, `ASC NULLS LAST`, `ASC NULLS FIRST`, `DESC`, `DESC NULLS LAST`, `DESC NULLS FIRST` are allowed');

        $this->database
            ->select()
            ->from('table')
            ->orderBy('name', 'FOO')
            ->sqlStatement();
    }

    #[\Testo\Test]
    public function testOrderByJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->orderBy('logs->created_at', 'DESC');

        $this->assertSameQuery("SELECT * FROM {table} ORDER BY {logs}->>'created_at' DESC", $select);
    }
}
