<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\UpsertQuery;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class UpsertQueryTest extends BaseTest
{
    public const UPSERT_CLAUSE = 'ON DUPLICATE KEY UPDATE';

    public static function queryWithValuesDataProvider(): array
    {
        return [
            'compile' => [
                'table'    => 'table',
                'alias'    => 'target',
                'values'   => ['name' => 'Adam'],
                'compile'  => true,
                'expected' => \sprintf('INSERT INTO {table} ({name}) VALUES (\'Adam\') AS {target} %s {name} = {target}.{name}', static::UPSERT_CLAUSE),
            ],
            'upsert'  => [
                'table'    => 'table',
                'alias'    => 'target',
                'values'   => ['name' => 'Adam'],
                'compile'  => false,
                'expected' => \sprintf('INSERT INTO {table} ({name}) VALUES (?) AS {target} %s {name} = {target}.{name}', static::UPSERT_CLAUSE),
            ],
        ];
    }

    public static function queryWithStatesValuesDataProvider(): array
    {
        return [
            'compile' => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'balance'],
                'values'   => ['Adam', 400],
                'compile'  => true,
                'expected' => \sprintf('INSERT INTO {table} ({name}, {balance}) VALUES (\'Adam\', 400) AS {target} %s {name} = {target}.{name}, {balance} = {target}.{balance}', static::UPSERT_CLAUSE),
            ],
            'upsert'  => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'balance'],
                'values'   => ['Adam', 400],
                'compile'  => false,
                'expected' => \sprintf('INSERT INTO {table} ({name}, {balance}) VALUES (?, ?) AS {target} %s {name} = {target}.{name}, {balance} = {target}.{balance}', static::UPSERT_CLAUSE),
            ],
        ];
    }

    public static function queryWithMultipleRowsDataProvider(): array
    {
        return [
            'compile' => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'balance'],
                'values'   => [
                    ['Adam', 400],
                    ['John', 200],
                ],
                'compile'  => true,
                'expected' => \sprintf('INSERT INTO {table} ({name}, {balance}) VALUES (\'Adam\', 400), (\'John\', 200) AS {target} %s {name} = {target}.{name}, {balance} = {target}.{balance}', static::UPSERT_CLAUSE),
            ],
            'upsert'  => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'balance'],
                'values'   => [
                    ['Adam', 400],
                    ['John', 200],
                ],
                'compile'  => false,
                'expected' => \sprintf('INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?) AS {target} %s {name} = {target}.{name}, {balance} = {target}.{balance}', static::UPSERT_CLAUSE),
            ],
        ];
    }

    public static function queryWithMultipleRowsAsArrayDataProvider(): array
    {
        return [
            'compile' => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'balance'],
                'values'   => [
                    ['name' => 'Adam', 'balance' => 400],
                    ['name' => 'John', 'balance' => 200],
                ],
                'compile'  => true,
                'expected' => \sprintf('INSERT INTO {table} ({name}, {balance}) VALUES (\'Adam\', 400), (\'John\', 200) AS {target} %s {name} = {target}.{name}, {balance} = {target}.{balance}', static::UPSERT_CLAUSE),
            ],
            'upsert' => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'balance'],
                'values'   => [
                    ['name' => 'Adam', 'balance' => 400],
                    ['name' => 'John', 'balance' => 200],
                ],
                'compile'  => false,
                'expected' => \sprintf('INSERT INTO {table} ({name}, {balance}) VALUES (?, ?), (?, ?) AS {target} %s {name} = {target}.{name}, {balance} = {target}.{balance}', static::UPSERT_CLAUSE),
            ],
            'expression' => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'created_at', 'updated_at', 'deleted_at'],
                'values'   => [
                    'name' => 'Adam',
                    'created_at' => new Expression('NOW()'),
                    'updated_at' => new Expression('NOW()'),
                    'deleted_at' => null,
                ],
                'compile'  => false,
                'expected' => \sprintf('INSERT INTO {table} ({name}, {created_at}, {updated_at}, {deleted_at}) VALUES (?, NOW(), NOW(), ?) AS {target} %s {name} = {target}.{name}, {created_at} = {target}.{created_at}, {updated_at} = {target}.{updated_at}, {deleted_at} = {target}.{deleted_at}', static::UPSERT_CLAUSE),
                'params'   => ['Adam', null],
            ],
            'fragment' => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'created_at', 'updated_at', 'deleted_at'],
                'values'   => [
                    'name' => 'Adam',
                    'created_at' => new Fragment('NOW()'),
                    'updated_at' => new Fragment('NOW()'),
                    'deleted_at' => new Fragment('datetime(\'now\')'),
                ],
                'compile'  => false,
                'expected' => \sprintf('INSERT INTO {table} ({name}, {created_at}, {updated_at}, {deleted_at}) VALUES (?, NOW(), NOW(), datetime(\'now\')) AS {target} %s {name} = {target}.{name}, {created_at} = {target}.{created_at}, {updated_at} = {target}.{updated_at}, {deleted_at} = {target}.{deleted_at}', static::UPSERT_CLAUSE),
            ],
        ];
    }

    public static function queryWithCustomFragmentDataProvider(): array
    {
        return [
            'compile' => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'updated_at'],
                'values'   => [
                    'name' => 'Adam',
                ],
                'compile'  => true,
                'expected' => \sprintf(\sprintf('INSERT INTO {table} ({name}, {updated_at}) VALUES (\'Adam\', NOW()) AS {target} %s {name} = {target}.{name}, {updated_at} = {target}.{updated_at}', static::UPSERT_CLAUSE), static::UPSERT_CLAUSE),
                'params'   => ['Adam'],
            ],
            'upsert' => [
                'table'    => 'table',
                'alias'    => 'target',
                'columns'  => ['name', 'updated_at'],
                'values'   => [
                    'name' => 'Adam',
                ],
                'compile'  => false,
                'expected' => \sprintf(\sprintf('INSERT INTO {table} ({name}, {updated_at}) VALUES (?, NOW()) AS {target} %s {name} = {target}.{name}, {updated_at} = {target}.{updated_at}', static::UPSERT_CLAUSE), static::UPSERT_CLAUSE),
                'params'   => ['Adam'],
            ],
        ];
    }

    public function testQueryInstance(): void
    {
        $this->assertInstanceOf(
            UpsertQuery::class,
            $this->database->upsert(),
        );

        $this->assertInstanceOf(
            UpsertQuery::class,
            $this->database->table->upsert(),
        );
    }

    public function testNoColumnsThrowsException(): void
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage('Upsert query must define at least one column');

        $this->db()->upsert('table')->values([])->__toString();
    }

    /**
     * @dataProvider queryWithValuesDataProvider
     */
    public function testQueryWithValues(
        string $table,
        string $alias,
        array $values,
        bool $compile,
        string $expected,
    ): void {
        $upsert = $this->db()->upsert($table)->alias($alias)->values($values);

        $actual = $compile ? $upsert->__toString() : $upsert;

        $this->assertSameQuery($expected, $actual);
    }

    /**
     * @dataProvider queryWithStatesValuesDataProvider
     */
    public function testQueryWithStatesValues(
        string $table,
        string $alias,
        array $columns,
        array $values,
        bool $compile,
        string $expected,
    ): void {
        $upsert = $this->database->upsert()->into($table)->alias($alias)->columns(...$columns)->values(...$values);

        $actual = $compile ? $upsert->__toString() : $upsert;

        $this->assertSameQuery($expected, $actual);
    }

    /**
     * @dataProvider queryWithMultipleRowsDataProvider
     */
    public function testQueryWithMultipleRows(
        string $table,
        string $alias,
        array $columns,
        array $values,
        bool $compile,
        string $expected,
    ): void {
        $upsert = $this->database->upsert()->into($table)->alias($alias)->columns(...$columns);

        foreach ($values as $row) {
            $upsert->values(...$row);
        }

        $actual = $compile ? $upsert->__toString() : $upsert;

        $this->assertSameQuery($expected, $actual);
    }

    /**
     * @dataProvider queryWithMultipleRowsAsArrayDataProvider
     */
    public function testQueryWithMultipleRowsAsArray(
        string $table,
        string $alias,
        array $columns,
        array $values,
        bool $compile,
        string $expected,
        ?array $params = null,
    ): void {
        $upsert = $this->database->upsert()->into($table)->alias($alias)->columns(...$columns)->values($values);

        $actual = $compile ? $upsert->__toString() : $upsert;

        $this->assertSameQuery($expected, $actual);

        if ($params !== null) {
            $this->assertSameParameters($params, $upsert);
        }
    }

    /**
     * @dataProvider queryWithCustomFragmentDataProvider
     */
    public function testQueryWithCustomFragment(
        string $table,
        string $alias,
        array $columns,
        array $values,
        bool $compile,
        string $expected,
        ?array $params = null,
    ): void {
        $fragment = $this->createMock(FragmentInterface::class);
        $fragment->method('getType')->willReturn(CompilerInterface::FRAGMENT);
        $fragment->method('getTokens')->willReturn([
            'fragment' => 'NOW()',
            'parameters' => [],
        ]);

        $values['updated_at'] = $fragment;

        $upsert = $this->database->upsert()->into($table)->alias($alias)->columns(...$columns)->values($values);
        $actual = $compile ? $upsert->__toString() : $upsert;

        $this->assertSameQuery($expected, $actual);

        if ($params !== null) {
            $this->assertSameParameters($params, $upsert);
        }

        // cached query
        $upsert = $this->database->upsert()->into($table)->alias($alias)->columns(...$columns)->values($values);
        $actual = $compile ? $upsert->__toString() : $upsert;

        $this->assertSameQuery($expected, $actual);

        if ($params !== null) {
            $this->assertSameParameters($params, $upsert);
        }
    }
}
