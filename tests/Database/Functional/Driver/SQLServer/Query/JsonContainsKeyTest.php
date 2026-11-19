<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLServer\Query;

use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class JsonContainsKeyTest extends BaseTest
{
    public const DRIVER = 'sqlserver';

    public static function provideInjectedConditions(): iterable
    {
        yield 'whereJsonContainsKey' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJsonContainsKey("data->x' IN ('y') OR 1=1 OR 'a"),
        ];
        yield 'whereJsonDoesntContainKey' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJsonDoesntContainKey("data->tags' IN ('y') OR 1=1 OR 'a"),
        ];
        yield 'whereJsonContainsKey array index' => [
            static fn(SelectQuery|UpdateQuery|DeleteQuery $q) => $q
                ->whereJsonContainsKey('data->tags[0 IN (0) OR 1=1 OR 0]'),
        ];
    }

    public static function provideKeys(): iterable
    {
        yield 'plain key' => ['data->tags', [10, 20]];
        yield 'key with an apostrophe' => ["data->it's", [10]];
        yield 'array index' => ['data->tags[1]', [10]];
        yield 'chained array indexes' => ['data->matrix[1][2]', [10]];
        yield 'non-latin key' => ['data->ключ', [10]];
        yield 'non-latin nested key' => ['data->ключ->вложенный', [10]];
    }

    /**
     * @dataProvider provideInjectedConditions
     */
    public function testSelectDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->where('tenant_id', 10);
        $condition($select);

        $this->assertNotContains(20, \array_column($select->fetchAll(), 'tenant_id'));
    }

    /**
     * @dataProvider provideInjectedConditions
     */
    public function testUpdateDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $update = $this->database->update('documents', ['data' => '{}'])->where('tenant_id', 10);
        $condition($update);
        $update->run();

        $data = $this->database->select('data')->from('documents')->where('tenant_id', 20)->run()->fetchColumn();
        $this->assertStringContainsString('confidential', $data);
    }

    /**
     * @dataProvider provideInjectedConditions
     */
    public function testDeleteDoesNotEscapeTenantScope(\Closure $condition): void
    {
        $delete = $this->database->delete('documents')->where('tenant_id', 10);
        $condition($delete);
        $delete->run();

        $this->assertSame(1, $this->database->select()->from('documents')->where('tenant_id', 20)->count());
    }

    /**
     * @dataProvider provideKeys
     */
    public function testContainsKey(string $path, array $expected): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->whereJsonContainsKey($path)->orderBy('tenant_id');

        $this->assertSame($expected, \array_map('intval', \array_column($select->fetchAll(), 'tenant_id')));
    }

    public function testWhereJsonWithNonLatinPath(): void
    {
        $select = $this->database->select('tenant_id')->from('documents')->whereJson('data->ключ->вложенный', 'да');

        $this->assertSame([10], \array_map('intval', \array_column($select->fetchAll(), 'tenant_id')));
    }

    public function setUp(): void
    {
        parent::setUp();

        // nvarchar, not the varchar that the `json` type maps to: only it keeps non-latin keys intact.
        $this->database->execute('CREATE TABLE documents (id INT IDENTITY PRIMARY KEY, tenant_id INT, data NVARCHAR(MAX))');

        // In tenant 20 `tags` is an object, so its keys are not numeric.
        $this->database->insert('documents')->columns(['tenant_id', 'data'])->values([
            [10, '{"tags": ["a", "b"], "matrix": [[0], [0, 1, 2]], "it\'s": "yes", "ключ": {"вложенный": "да"}}'],
            [20, '{"tags": {"work": "confidential"}, "matrix": [[0, 1, 2]]}'],
        ])->run();
    }
}
