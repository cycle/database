<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\UpdateQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class UpdateQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testUpdateWithWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery("UPDATE {table} SET {some} = ? WHERE {settings}->>'theme' = ?", $select);
        $this->assertSameParameters(['value', 'dark'], $select);
    }

    public function testUpdateWithAndWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->andWhereJson('settings->theme', 'dark');

        $this->assertSameQuery("UPDATE {table} SET {some} = ? WHERE {id} = ? AND {settings}->>'theme' = ?", $select);
        $this->assertSameParameters(['value', 1, 'dark'], $select);
    }

    public function testUpdateWithOrWhereJson(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJson('settings->theme', 'dark');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR {settings}->>'theme' = ?",
            $select
        );
        $this->assertSameParameters(['value', 1, 'dark'], $select);
    }

    public function testUpdateWithWhereJsonNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery("UPDATE {table} SET {some} = ? WHERE {settings}->'phone'->>'work' = ?", $select);
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    public function testUpdateWithWhereJsonArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery("UPDATE {table} SET {some} = ? WHERE {settings}->'phones'->>1 = ?", $select);
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    public function testUpdateWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {settings}->'phones'->1->'numbers'->>3 = ?",
            $select
        );
        $this->assertSameParameters(['value', '+1234567890'], $select);
    }

    public function testUpdateWithWhereJsonContains(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE ({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', json_encode('en')], $select);
    }

    public function testUpdateWithAndWhereJsonContains(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->andWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? AND ({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', 1, json_encode('en')], $select);
    }

    public function testUpdateWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR ({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', 1, json_encode('en')], $select);
    }

    public function testUpdateWithWhereJsonContainsNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE({settings}->'phones'->'work')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', json_encode('+1234567890')], $select);
    }

    public function testUpdateWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE ({settings}->'phones'->1)::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', json_encode('+1234567890')], $select);
    }

    public function testUpdateWithWhereJsonContainsNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonContains('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', json_encode('+1234567890')], $select);
    }

    public function testUpdateWithWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT ({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', json_encode('en')], $select);
    }

    public function testUpdateWithAndWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->andWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? AND NOT ({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', 1, json_encode('en')], $select);
    }

    public function testUpdateWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR NOT({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', 1, json_encode('en')], $select);
    }

    public function testUpdateWithWhereJsonDoesntContainNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones->work', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT ({settings}->'phones'->'work')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', json_encode('+1234567890')], $select);
    }

    public function testUpdateWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT ({settings}->'phones'->1)::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', json_encode('+1234567890')], $select);
    }

    public function testUpdateWithWhereJsonDoesntContainNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonDoesntContain('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE NOT ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?",
            $select
        );
        $this->assertSameParameters(['value', json_encode('+1234567890')], $select);
    }

    public function testUpdateWithWhereJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->languages', 1);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'languages')::jsonb) = ?",
            $select
        );
        $this->assertSameParameters(['value', 1], $select);
    }

    public function testUpdateWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'languages')::jsonb) >= ?",
            $select
        );
        $this->assertSameParameters(['value', 1], $select);
    }

    public function testUpdateWithAndJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->andWhereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? AND jsonb_array_length(({settings}->'languages')::jsonb) = ?",
            $select
        );
        $this->assertSameParameters(['value', 1, 3], $select);
    }

    public function testUpdateWithOrWhereJsonJsonLength(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->where('id', 1)
            ->orWhereJsonLength('settings->languages', 4);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE {id} = ? OR jsonb_array_length(({settings}->'languages')::jsonb) = ?",
            $select
        );
        $this->assertSameParameters(['value', 1, 4], $select);
    }

    public function testUpdateWithWhereJsonLengthNested(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->personal->languages', 1);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'personal'->'languages')::jsonb) = ?",
            $select
        );
        $this->assertSameParameters(['value', 1], $select);
    }

    public function testUpdateWithWhereJsonLengthArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->phones[1]', 2);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'phones'->1)::jsonb) = ?",
            $select
        );
        $this->assertSameParameters(['value', 2], $select);
    }

    public function testUpdateWithWhereJsonLengthNestedArray(): void
    {
        $select = $this->database
            ->update('table')
            ->values(['some' => 'value'])
            ->whereJsonLength('settings->phones[1]->numbers[3]', 5);

        $this->assertSameQuery(
            "UPDATE {table} SET {some} = ? WHERE jsonb_array_length(({settings}->'phones'->1->'numbers'->3)::jsonb) = ?",
            $select
        );
        $this->assertSameParameters(['value', 5], $select);
    }
}
