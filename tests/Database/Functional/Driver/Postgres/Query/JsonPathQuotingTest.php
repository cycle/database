<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Postgres\Query;

use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * JSON path segments that contain apostrophes must stay inside the SQL string literal.
 */
#[\Testo\Filter\Group('driver', 'driver-postgres')]
class JsonPathQuotingTest extends BaseTest
{
    public const DRIVER = 'postgres';

    public static function provideJsonConditions(): iterable
    {
        yield 'whereJson' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJson("data->classification' IS NOT NULL OR 'x", 'x'),
        ];
        yield 'whereJson nested path' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJson("data->meta' IS NOT NULL OR jsonb_build_object('k', 'x') #> '{}->k", 'x'),
        ];
        yield 'whereJsonContains' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJsonContains("data->tags')::jsonb IS NOT NULL OR ('1", 1),
        ];
        yield 'whereJsonDoesntContain' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJsonDoesntContain("data->tags')::jsonb IS NULL OR ('1", 1),
        ];
        yield 'whereJsonContainsKey' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJsonContainsKey("data->x', false) OR coalesce(true, 't"),
        ];
        yield 'whereJsonDoesntContainKey' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJsonDoesntContainKey("data->classification', false) OR coalesce(true, 't"),
        ];
        yield 'whereJsonLength' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJsonLength("data->tags')::jsonb) >= 0 OR jsonb_array_length(jsonb_build_array('x", 1),
        ];
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('provideJsonConditions')]
    public function testSelectDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->where('tenant_id', 10);
        $condition($select);

        \Testo\Assert::iterable(\array_column($select->fetchAll(), 'tenant_id'))->notContains(20);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('provideJsonConditions')]
    public function testUpdateDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $update = $this->database->update('documents', ['data' => '{}'])->where('tenant_id', 10);
        $condition($update);
        $update->run();

        $data = $this->database->select('data')->from('documents')->where('tenant_id', 20)->run()->fetchColumn();
        \Testo\Assert::string($data)->contains('confidential');
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('provideJsonConditions')]
    public function testDeleteDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $delete = $this->database->delete('documents')->where('tenant_id', 10);
        $condition($delete);
        $delete->run();

        \Testo\Assert::same($this->database->select()->from('documents')->where('tenant_id', 20)->count(), 1);
    }

    #[\Testo\Test]
    public function testWhereJsonWithApostropheInKey(): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->whereJson("data->it's", 'yes');

        \Testo\Assert::same(\array_column($select->fetchAll(), 'tenant_id'), [10]);
    }

    #[\Testo\Test]
    public function testWhereJsonContainsKeyWithApostropheInKey(): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->whereJsonContainsKey("data->it's");

        \Testo\Assert::same(\array_column($select->fetchAll(), 'tenant_id'), [10]);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->schema('documents');
        $schema->primary('id');
        $schema->integer('tenant_id');
        $schema->json('data');
        $schema->save();

        $this->database->insert('documents')->columns(['tenant_id', 'data'])->values([
            [10, '{"classification": "public", "tags": ["a"], "meta": {"k": "v"}, "it\'s": "yes"}'],
            [20, '{"classification": "confidential", "tags": ["b", "c"], "meta": {"k": "w"}}'],
        ])->run();
    }
}
