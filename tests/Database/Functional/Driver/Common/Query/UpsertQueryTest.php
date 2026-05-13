<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Query;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Exception\CompilerException;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\OnConflict;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

/**
 * Cross-driver behaviour expectations for upsert. Driver-specific SQL assertions live in
 * per-driver subclasses.
 */
abstract class UpsertQueryTest extends BaseTest
{
    public function testOnConflictTurnsInsertIntoUpsertType(): void
    {
        $q = $this->database->insert('table')->values(['email' => 'a@b.c']);
        $this->assertSame(CompilerInterface::INSERT_QUERY, $q->getType());

        $q->onConflict('email');
        $this->assertSame(CompilerInterface::UPSERT_QUERY, $q->getType());
    }

    public function testShorthandIsEquivalentToTargetDoUpdate(): void
    {
        $a = $this->database->insert('t')->values(['email' => 'x', 'name' => 'y'])
            ->onConflict('email');

        $b = $this->database->insert('t')->values(['email' => 'x', 'name' => 'y'])
            ->onConflict(OnConflict::target('email')->doUpdate());

        $this->assertSame($a->sqlStatement(), $b->sqlStatement());
    }

    public function testShorthandAcceptsArrayOfColumns(): void
    {
        $q = $this->database->insert('t')
            ->values(['tenant_id' => 1, 'email' => 'x', 'name' => 'y'])
            ->onConflict(['tenant_id', 'email']);

        $this->assertSame(CompilerInterface::UPSERT_QUERY, $q->getType());
        $this->assertInstanceOf(InsertQuery::class, $q);
    }

    public function testOnConflictReturnsInsertQuery(): void
    {
        $q = $this->database->insert('t')->values(['email' => 'x']);
        $this->assertSame($q, $q->onConflict('email'));
    }

    public function testEmptyValuesRejected(): void
    {
        $q = $this->database->insert('t')
            ->onConflict(OnConflict::target('email')->doUpdate());

        $this->expectException(CompilerException::class);
        (string) $q;
    }
}
