<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * Keys with quotes, backslashes and control characters must be looked up as they are, without ending the SQL literal
 * or the JSON path member.
 */
abstract class JsonPathEscapingTest extends BaseTest
{
    public static function provideKeys(): iterable
    {
        yield 'apostrophe' => ["data->it's", false];
        yield 'backslash before an apostrophe' => ["data->own\\'", true];
        yield 'backslash' => ['data->a\\b', true];
        yield 'double quote' => ['data->a"b', true];
        yield 'line feed' => ["data->a\nb", true];
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('provideKeys')]
    public function testWhereJson(string $path, bool $escaped): void
    {
        $escaped and $this->skipUnlessEscapedKeysSupported();

        $select = $this->database->select('tenant_id')->from('documents')->whereJson($path, 'yes');

        \Testo\Assert::same($this->tenants($select->fetchAll()), [10]);
    }

    #[\Testo\Test]
    #[\Testo\Data\DataProvider('provideKeys')]
    public function testWhereJsonContainsKey(string $path, bool $escaped): void
    {
        $escaped and $this->skipUnlessEscapedKeysSupported();

        $select = $this->database->select('tenant_id')->from('documents')->whereJsonContainsKey($path);

        \Testo\Assert::same($this->tenants($select->fetchAll()), [10]);
    }

    #[\Testo\Test]
    public function testQuoteInKeyReadsThatKey(): void
    {
        $this->skipUnlessEscapedKeysSupported();

        $select = fn(string $value): array => $this->tenants(
            $this->database->select('tenant_id')->from('documents')->whereJson('data->a"."secret', $value)->fetchAll(),
        );

        \Testo\Assert::same($select('literal'), [10]);
        \Testo\Assert::same($select('nested'), []);
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
        $this->supportsEscapedPathKeys() or (throw new \Testo\Core\Exception\SkipTest('The JSON path parser does not read escaped keys.'));
    }

    private function tenants(array $rows): array
    {
        return \array_map('intval', \array_column($rows, 'tenant_id'));
    }
}
