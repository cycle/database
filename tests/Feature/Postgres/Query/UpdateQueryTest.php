<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Query;

use Cycle\Database\Tests\Feature\Common\Query\UpdateQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Test;

final class UpdateQueryTest extends CommonClass
{
    #[Test]
    public function testUpdateWithWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? AND {settings}->>'theme' = ?");
        SqlAssert::sameParameters($select, ['value', 1, 'dark']);
    }

    #[Test]
    public function testUpdateWithOrWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR {settings}->>'theme' = ?");
        SqlAssert::sameParameters($select, ['value', 1, 'dark']);
    }

    #[Test]
    public function testUpdateWithWhereJsonNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {settings}->'phone'->>'work' = ?");
        SqlAssert::sameParameters($select, ['value', '+1234567890']);
    }

    #[Test]
    public function testUpdateWithWhereJsonArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {settings}->'phones'->>1 = ?");
        SqlAssert::sameParameters($select, ['value', '+1234567890']);
    }

    #[Test]
    public function testUpdateWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {settings}->'phones'->1->'numbers'->>3 = ?");
        SqlAssert::sameParameters($select, ['value', '+1234567890']);
    }

    #[Test]
    public function testUpdateWithWhereJsonContains(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? AND ({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', 1, \json_encode('en')]);
    }

    #[Test]
    public function testUpdateWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR ({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', 1, \json_encode('en')]);
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE ({settings}->'phones'->'work')::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', \json_encode('+1234567890')]);
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsSinglePath(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings', []);

        SqlAssert::same($select, 'UPDATE {table} SET {some} = ? WHERE ({settings})::jsonb @> ?');
        SqlAssert::sameParameters($select, ['value', \json_encode([])]);
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE ({settings}->'phones'->1)::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', \json_encode('+1234567890')]);
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', \json_encode('+1234567890')]);
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? AND NOT ({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', 1, \json_encode('en')]);
    }

    #[Test]
    public function testUpdateWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR NOT({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', 1, \json_encode('en')]);
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT ({settings}->'phones'->'work')::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', \json_encode('+1234567890')]);
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainSinglePath(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings', []);

        SqlAssert::same($select, 'UPDATE {table} SET {some} = ? WHERE NOT ({settings})::jsonb @> ?');
        SqlAssert::sameParameters($select, ['value', \json_encode([])]);
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT ({settings}->'phones'->1)::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', \json_encode('+1234567890')]);
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?");
        SqlAssert::sameParameters($select, ['value', \json_encode('+1234567890')]);
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? AND coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testUpdateWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE coalesce(({settings}->'phones')::jsonb ?? 'work', false)");
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE CASE WHEN jsonb_typeof(({settings}->'phones')::jsonb) = 'array'
                    THEN jsonb_array_length(({settings}->'phones')::jsonb) >= 2 ELSE false END");
    }

    #[Test]
    public function testUpdateWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE CASE WHEN jsonb_typeof(({settings}->'phones'->1->'numbers')::jsonb) = 'array'
                    THEN jsonb_array_length(({settings}->'phones'->1->'numbers')::jsonb) >= 4 ELSE false END");
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? AND NOT coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testUpdateWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR NOT coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT coalesce(({settings}->'phones')::jsonb ?? 'work', false)");
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT CASE WHEN jsonb_typeof(({settings}->'phones')::jsonb) = 'array'
                    THEN jsonb_array_length(({settings}->'phones')::jsonb) >= 2 ELSE false END");
    }

    #[Test]
    public function testUpdateWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE NOT CASE WHEN jsonb_typeof(({settings}->'phones'->1->'numbers')::jsonb) = 'array'
                THEN jsonb_array_length(({settings}->'phones'->1->'numbers')::jsonb) >= 4 ELSE false END");
    }

    #[Test]
    public function testUpdateWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'languages')::jsonb) >= ?");
        SqlAssert::sameParameters($select, ['value', 1]);
    }

    #[Test]
    public function testUpdateWithWhereJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? AND jsonb_array_length(({settings}->'languages')::jsonb) = ?");
        SqlAssert::sameParameters($select, ['value', 1, 3]);
    }

    #[Test]
    public function testUpdateWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE {id} = ? OR jsonb_array_length(({settings}->'languages')::jsonb) = ?");
        SqlAssert::sameParameters($select, ['value', 1, 4]);
    }

    #[Test]
    public function testUpdateWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'personal'->'languages')::jsonb) = ?");
        SqlAssert::sameParameters($select, ['value', 1]);
    }

    #[Test]
    public function testUpdateWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'phones'->1)::jsonb) = ?");
        SqlAssert::sameParameters($select, ['value', 2]);
    }

    #[Test]
    public function testUpdateWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'phones'->1->'numbers'->3)::jsonb) = ?");
        SqlAssert::sameParameters($select, ['value', 5]);
    }
}
