<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Query;

use Cycle\Database\Tests\Feature\Common\Query\DeleteQueryTest as CommonClass;
use Cycle\Testing\Database\Assert\SqlAssert;
use Testo\Test;

final class DeleteQueryTest extends CommonClass
{
    #[Test]
    public function testDeleteWithWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJson('settings->theme', 'dark');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND {settings}->>'theme' = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testDeleteWithOrWhereJson(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR {settings}->>'theme' = ?");
        SqlAssert::sameParameters($select, [1, 'dark']);
    }

    #[Test]
    public function testDeleteWithWhereJsonNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phone->work', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {settings}->'phone'->>'work' = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {settings}->'phones'->>1 = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {settings}->'phones'->1->'numbers'->>3 = ?");
        SqlAssert::sameParameters($select, ['+1234567890']);
    }

    #[Test]
    public function testDeleteWithWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND ({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, [1, \json_encode('en')]);
    }

    #[Test]
    public function testDeleteWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR ({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, [1, \json_encode('en')]);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ({settings}->'phones'->'work')::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsSinglePath(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings', []);

        SqlAssert::same($select, 'DELETE FROM {table} WHERE ({settings})::jsonb @> ?');
        SqlAssert::sameParameters($select, [\json_encode([])]);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ({settings}->'phones'->1)::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND NOT ({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, [1, \json_encode('en')]);
    }

    #[Test]
    public function testDeleteWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR NOT ({settings}->'languages')::jsonb @> ?");
        SqlAssert::sameParameters($select, [1, \json_encode('en')]);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT ({settings}->'phones'->'work')::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainSinglePath(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings', []);

        SqlAssert::same($select, 'DELETE FROM {table} WHERE NOT ({settings})::jsonb @> ?');
        SqlAssert::sameParameters($select, [\json_encode([])]);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT ({settings}->'phones'->1)::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?");
        SqlAssert::sameParameters($select, [\json_encode('+1234567890')]);
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testDeleteWithOrWhereJsonContainsKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonContainsKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones->work');

        SqlAssert::same($select, "DELETE FROM {table} WHERE coalesce(({settings}->'phones')::jsonb ?? 'work', false)");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE CASE WHEN jsonb_typeof(({settings}->'phones')::jsonb) = 'array' THEN jsonb_array_length(({settings}->'phones')::jsonb) >= 2 ELSE false END");
    }

    #[Test]
    public function testDeleteWithWhereJsonContainsKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonContainsKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE CASE WHEN jsonb_typeof(({settings}->'phones'->1->'numbers')::jsonb) = 'array'
                    THEN jsonb_array_length(({settings}->'phones'->1->'numbers')::jsonb) >= 4 ELSE false END");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? AND NOT coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testDeleteWithOrWhereJsonDoesntContainKey(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContainKey('settings->languages');

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR NOT coalesce(({settings})::jsonb ?? 'languages', false)");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones->work');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT coalesce(({settings}->'phones')::jsonb ?? 'work', false)");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT CASE WHEN jsonb_typeof(({settings}->'phones')::jsonb) = 'array'
                THEN jsonb_array_length(({settings}->'phones')::jsonb) >= 2 ELSE false END");
    }

    #[Test]
    public function testDeleteWithWhereJsonDoesntContainKeyNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonDoesntContainKey('settings->phones[1]->numbers[3]');

        SqlAssert::same($select, "DELETE FROM {table} WHERE NOT CASE WHEN jsonb_typeof(({settings}->'phones'->1->'numbers')::jsonb) = 'array'
                THEN jsonb_array_length(({settings}->'phones'->1->'numbers')::jsonb) >= 4 ELSE false END");
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        SqlAssert::same($select, "DELETE FROM {table} WHERE jsonb_array_length(({settings}->'languages')::jsonb) >= ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->whereJsonLength('settings->languages', 3);

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} =? AND jsonb_array_length(({settings}->'languages')::jsonb) =? ");
        SqlAssert::sameParameters($select, [1, 3]);
    }

    #[Test]
    public function testDeleteWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->delete('table')
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        SqlAssert::same($select, "DELETE FROM {table} WHERE {id} = ? OR jsonb_array_length(({settings}->'languages')::jsonb) = ?");
        SqlAssert::sameParameters($select, [1, 4]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->personal->languages', 1);

        SqlAssert::same($select, "DELETE FROM {table} WHERE jsonb_array_length(({settings}->'personal'->'languages')::jsonb) = ?");
        SqlAssert::sameParameters($select, [1]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]', 2);

        SqlAssert::same($select, "DELETE FROM {table} WHERE jsonb_array_length(({settings}->'phones'->1)::jsonb) = ?");
        SqlAssert::sameParameters($select, [2]);
    }

    #[Test]
    public function testDeleteWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->delete('table')
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        SqlAssert::same($select, "DELETE FROM {table} WHERE jsonb_array_length(({settings}->'phones'->1->'numbers'->3)::jsonb) = ?");
        SqlAssert::sameParameters($select, [5]);
    }
}
