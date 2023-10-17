<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class SelectQueryTest extends CommonClass
{
    public const DRIVER = 'postgres';

    public function testSelectWithWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->theme', 'dark');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->>'theme' = ?", $select);
        $this->assertSameParameters(['dark'], $select);
    }

    public function testSelectWithAndWhereJson(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->andWhereJson('settings->theme', 'dark');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {id} = ? AND {settings}->>'theme' = ?", $select);
        $this->assertSameParameters([1, 'dark'], $select);
    }

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

    public function testSelectWithWhereJsonNested(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phone->work', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phone'->>'work' = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phones'->>1 = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonNestedArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJson('settings->phones[1]->numbers[3]', '+1234567890');

        $this->assertSameQuery("SELECT * FROM {table} WHERE {settings}->'phones'->1->'numbers'->>3 = ?", $select);
        $this->assertSameParameters(['+1234567890'], $select);
    }

    public function testSelectWithWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters([json_encode('en')], $select);
    }

    public function testSelectWithAndWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->andWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND ({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters([1, json_encode('en')], $select);
    }

    public function testSelectWithOrWhereJsonContains(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonContains('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR ({settings}->'languages')::jsonb @> ?",
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
            "SELECT * FROM {table} WHERE ({settings}->'phones'->'work')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonContainsArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonContains('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE ({settings}->'phones'->1)::jsonb @> ?",
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
            "SELECT * FROM {table} WHERE ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?",
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
            "SELECT * FROM {table} WHERE NOT ({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters([json_encode('en')], $select);
    }

    public function testSelectWithAndWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->andWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND NOT({settings}->'languages')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters([1, json_encode('en')], $select);
    }

    public function testSelectWithOrWhereJsonDoesntContain(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->orWhereJsonDoesntContain('settings->languages', 'en');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? OR NOT({settings}->'languages')::jsonb @>?",
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
            "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->'work')::jsonb @> ?",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonDoesntContainArray(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonDoesntContain('settings->phones[1]', '+1234567890');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->1)::jsonb @> ?",
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
            "SELECT * FROM {table} WHERE NOT ({settings}->'phones'->1->'numbers'->3)::jsonb @> ?",
            $select
        );
        $this->assertSameParameters([json_encode('+1234567890')], $select);
    }

    public function testSelectWithWhereJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'languages')::jsonb) = ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testSelectWithWhereJsonLengthAndCustomOperator(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->whereJsonLength('settings->languages', 1, '>=');

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'languages')::jsonb) >= ?",
            $select
        );
        $this->assertSameParameters([1], $select);
    }

    public function testSelectWithAndJsonLength(): void
    {
        $select = $this->database
            ->select()
            ->from('table')
            ->where('id', 1)
            ->andWhereJsonLength('settings->languages', 3);

        $this->assertSameQuery(
            "SELECT * FROM {table} WHERE {id} = ? AND jsonb_array_length(({settings}->'languages')::jsonb) = ?",
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
            "SELECT * FROM {table} WHERE {id} = ? OR jsonb_array_length(({settings}->'languages')::jsonb) = ?",
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
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'personal'->'languages')::jsonb) = ?",
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
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'phones'->1)::jsonb) = ?",
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
            "SELECT * FROM {table} WHERE jsonb_array_length(({settings}->'phones'->1->'numbers'->3)::jsonb) = ?",
            $select
        );
        $this->assertSameParameters([5], $select);
    }
}
