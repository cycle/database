<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Feature\Common\Query;

use Cycle\Testing\Database\Attribute\ExceptDriver;
use Cycle\Testing\Database\DatabaseTestCase;
use Cycle\Testing\Database\Driver;
use Testo\Assert;
use Testo\Core\Exception\SkipTest;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

/**
 * Keys with quotes, backslashes and control characters must be looked up as they are, without ending the SQL literal
 * or the JSON path member.
 */
#[ExceptDriver(Driver::SQLite)]
class JsonPathEscapingTest extends DatabaseTestCase
{
    public static function provideKeys(): iterable
    {
        yield 'apostrophe' => ["data->it's", false];
        yield 'backslash before an apostrophe' => ["data->own\\'", true];
        yield 'backslash' => ['data->a\\b', true];
        yield 'double quote' => ['data->a"b', true];
        yield 'line feed' => ["data->a\nb", true];
    }

    #[Test]
    #[DataProvider('provideKeys')]
    public function testWhereJson(string $path, bool $escaped): void
    {
        $escaped and $this->skipUnlessEscapedKeysSupported();

        $select = $this->database->select('tenant_id')->from('documents')->whereJson($path, 'yes');

        Assert::same($this->tenants($select->fetchAll()), [10]);
    }

    #[Test]
    #[DataProvider('provideKeys')]
    public function testWhereJsonContainsKey(string $path, bool $escaped): void
    {
        $escaped and $this->skipUnlessEscapedKeysSupported();

        $select = $this->database->select('tenant_id')->from('documents')->whereJsonContainsKey($path);

        Assert::same($this->tenants($select->fetchAll()), [10]);
    }

    #[Test]
    public function testQuoteInKeyReadsThatKey(): void
    {
        $this->skipUnlessEscapedKeysSupported();

        $select = fn(string $value): array => $this->tenants(
            $this->database->select('tenant_id')->from('documents')->whereJson('data->a"."secret', $value)->fetchAll(),
        );

        Assert::same($select('literal'), [10]);
        Assert::same($select('nested'), []);
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
            [10, \json_encode([
                "it's" => 'yes',
                "own\\'" => 'yes',
                'a\\b' => 'yes',
                'a"b' => 'yes',
                "a\nb" => 'yes',
                'a"."secret' => 'literal',
                'a' => ['secret' => 'nested'],
            ])],
            [20, \json_encode(['other' => 'no'])],
        ])->run();
    }

    /**
     * Whether the database reads JSON escapes inside a quoted path member.
     */
    protected function supportsEscapedPathKeys(): bool
    {
        return true;
    }

    private function skipUnlessEscapedKeysSupported(): void
    {
        $this->supportsEscapedPathKeys() or (throw new SkipTest('The JSON path parser does not read escaped keys.'));
    }

    private function tenants(array $rows): array
    {
        return \array_map('intval', \array_column($rows, 'tenant_id'));
    }
}
