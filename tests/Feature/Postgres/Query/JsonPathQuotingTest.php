<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Postgres\Query;

use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Cycle\Testing\Database\DatabaseTestCase;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

/**
 * JSON path segments that contain apostrophes must stay inside the SQL string literal.
 */
final class JsonPathQuotingTest extends DatabaseTestCase
{
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

    #[Test]
    #[DataProvider('provideJsonConditions')]
    public function testSelectDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->where('tenant_id', 10);
        $condition($select);

        Assert::iterable(\array_column($select->fetchAll(), 'tenant_id'))->notContains(20);
    }

    #[Test]
    #[DataProvider('provideJsonConditions')]
    public function testUpdateDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $update = $this->database->update('documents', ['data' => '{}'])->where('tenant_id', 10);
        $condition($update);
        $update->run();

        $data = $this->database->select('data')->from('documents')->where('tenant_id', 20)->run()->fetchColumn();
        Assert::string($data)->contains('confidential');
    }

    #[Test]
    #[DataProvider('provideJsonConditions')]
    public function testDeleteDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $delete = $this->database->delete('documents')->where('tenant_id', 10);
        $condition($delete);
        $delete->run();

        Assert::same($this->database->select()->from('documents')->where('tenant_id', 20)->count(), 1);
    }

    #[Test]
    public function testWhereJsonWithApostropheInKey(): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->whereJson("data->it's", 'yes');

        Assert::same(\array_column($select->fetchAll(), 'tenant_id'), [10]);
    }

    #[Test]
    public function testWhereJsonContainsKeyWithApostropheInKey(): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->whereJsonContainsKey("data->it's");

        Assert::same(\array_column($select->fetchAll(), 'tenant_id'), [10]);
    }

    #[BeforeTest]
    public function setUp(): void
    {
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
