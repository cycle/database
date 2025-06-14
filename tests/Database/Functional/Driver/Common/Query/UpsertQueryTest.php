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
    protected const QUERY_INSTANCE             = UpsertQuery::class;
    protected const QUERY_REQUIRES_CONFLICTS   = true;
    protected const QUERY_WITH_VALUES          = 'INSERT INTO {table} ({email}, {name}) VALUES (?, ?) ON CONFLICT ({email}) DO UPDATE SET {email} = EXCLUDED.{email}, {name} = EXCLUDED.{name}';
    protected const QUERY_WITH_STATES_VALUES   = 'INSERT INTO {table} ({email}, {name}) VALUES (?, ?) ON CONFLICT ({email}) DO UPDATE SET {email} = EXCLUDED.{email}, {name} = EXCLUDED.{name}';
    protected const QUERY_WITH_MULTIPLE_ROWS   = 'INSERT INTO {table} ({email}, {name}) VALUES (?, ?), (?, ?) ON CONFLICT ({email}) DO UPDATE SET {email} = EXCLUDED.{email}, {name} = EXCLUDED.{name}';
    protected const QUERY_WITH_EXPRESSIONS     = 'INSERT INTO {table} ({email}, {name}, {created_at}, {updated_at}, {deleted_at}) VALUES (?, ?, NOW(), NOW(), ?) ON CONFLICT ({email}) DO UPDATE SET {email} = EXCLUDED.{email}, {name} = EXCLUDED.{name}, {created_at} = EXCLUDED.{created_at}, {updated_at} = EXCLUDED.{updated_at}, {deleted_at} = EXCLUDED.{deleted_at}';
    protected const QUERY_WITH_FRAGMENTS       = 'INSERT INTO {table} ({email}, {name}, {created_at}, {updated_at}, {deleted_at}) VALUES (?, ?, NOW(), datetime(\'now\'), ?) ON CONFLICT ({email}) DO UPDATE SET {email} = EXCLUDED.{email}, {name} = EXCLUDED.{name}, {created_at} = EXCLUDED.{created_at}, {updated_at} = EXCLUDED.{updated_at}, {deleted_at} = EXCLUDED.{deleted_at}';
    protected const QUERY_WITH_CUSTOM_FRAGMENT = 'INSERT INTO {table} ({email}, {name}, {expired_at}) VALUES (?, ?, NOW()) ON CONFLICT ({email}) DO UPDATE SET {email} = EXCLUDED.{email}, {name} = EXCLUDED.{name}, {expired_at} = EXCLUDED.{expired_at}';

    public function testQueryInstance(): void
    {
        $this->assertInstanceOf(static::QUERY_INSTANCE, $this->database->upsert());
        $this->assertInstanceOf(static::QUERY_INSTANCE, $this->database->table->upsert());
    }

    public function testNoConflictsThrowsException(): void
    {
        if (static::QUERY_REQUIRES_CONFLICTS) {
            $this->expectException(CompilerException::class);
            $this->expectExceptionMessage('Upsert query must define conflicting index column names');

            $this->db()->upsert('table')
                ->values(
                    [
                        'email' => 'adam@email.com',
                        'name' => 'Adam',
                    ],
                )->__toString();
        } else {
            $this->assertFalse(static::QUERY_REQUIRES_CONFLICTS);
        }
    }

    public function testNoColumnsThrowsException(): void
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage('Upsert query must define at least one column');

        $this->db()->upsert('table')
            ->conflicts('email')
            ->values([])->__toString();
    }

    public function testQueryWithValues(): void
    {
        $upsert = $this->db()->upsert('table')
            ->conflicts('email')
            ->values(
                [
                    'email' => 'adam@email.com',
                    'name' => 'Adam',
                ],
            );

        $this->assertSameQuery(static::QUERY_WITH_VALUES, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam'], $upsert);
    }

    public function testQueryWithStatesValues(): void
    {
        $upsert = $this->database->upsert('table')
            ->conflicts('email')
            ->columns('email', 'name')
            ->values('adam@email.com', 'Adam');

        $this->assertSameQuery(static::QUERY_WITH_STATES_VALUES, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam'], $upsert);
    }

    public function testQueryWithMultipleRows(): void
    {
        $upsert = $this->database->upsert('table')
            ->conflicts('email')
            ->columns('email', 'name')
            ->values('adam@email.com', 'Adam')
            ->values('bill@email.com', 'Bill');

        $this->assertSameQuery(static::QUERY_WITH_MULTIPLE_ROWS, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam', 'bill@email.com', 'Bill'], $upsert);
    }

    public function testQueryWithMultipleRowsAsArray(): void
    {
        $upsert = $this->database->upsert('table')
            ->conflicts('email')
            ->values([
                ['email' => 'adam@email.com', 'name' => 'Adam'],
                ['email' => 'bill@email.com', 'name' => 'Bill'],
            ]);

        $this->assertSameQuery(static::QUERY_WITH_MULTIPLE_ROWS, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam', 'bill@email.com', 'Bill'], $upsert);
    }

    public function testQueryWithExpressions(): void
    {
        $upsert = $this->database->upsert('table')
            ->conflicts('email')
            ->values([
                'email' => 'adam@email.com',
                'name' => 'Adam',
                'created_at' => new Expression('NOW()'),
                'updated_at' => new Expression('NOW()'),
                'deleted_at' => null,
            ]);

        $this->assertSameQuery(static::QUERY_WITH_EXPRESSIONS, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam', null], $upsert);
    }

    public function testQueryWithFragments(): void
    {
        $upsert = $this->database->upsert('table')
            ->conflicts('email')
            ->values([
                'email' => 'adam@email.com',
                'name' => 'Adam',
                'created_at' => new Fragment('NOW()'),
                'updated_at' => new Fragment('datetime(\'now\')'),
                'deleted_at' => null,
            ]);

        $this->assertSameQuery(static::QUERY_WITH_FRAGMENTS, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam', null], $upsert);
    }

    public function testQueryWithCustomFragment(): void
    {
        $fragment = $this->createMock(FragmentInterface::class);
        $fragment->method('getType')->willReturn(CompilerInterface::FRAGMENT);
        $fragment->method('getTokens')->willReturn([
            'fragment' => 'NOW()',
            'parameters' => [],
        ]);

        $upsert = $this->database->upsert('table')
            ->conflicts('email')
            ->values([
                'email' => 'adam@email.com',
                'name' => 'Adam',
                'expired_at' => $fragment,
            ]);

        $this->assertSameQuery(static::QUERY_WITH_CUSTOM_FRAGMENT, $upsert);
        $this->assertSameParameters(['adam@email.com', 'Adam'], $upsert);
    }
}
