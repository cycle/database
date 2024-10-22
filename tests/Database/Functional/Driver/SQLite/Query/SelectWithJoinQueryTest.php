<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\SQLite\Query;

// phpcs:ignore
use Cycle\Database\Tests\Functional\Driver\Common\Query\SelectWithJoinQueryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class SelectWithJoinQueryTest extends CommonClass
{
    public const DRIVER = 'sqlite';

    public function testCacheJoinWithFullTypeName(): void
    {
        $compiler = $this->database->select()->getDriver()->getQueryCompiler();

        $ref = new \ReflectionProperty($compiler, 'cache');
        $ref->setAccessible(true);
        $ref->setValue($compiler, []);

        $select = $this->database->select()
            ->from(['users'])
            ->join('INNER', 'photos')->on('photos.user_id', 'users.id');

        $sql1 = $select->sqlStatement();
        $cache1 = $ref->getValue($compiler);

        $select = $this->database->select()
            ->from(['users'])
            ->join('INNER JOIN', 'photos')->on('photos.user_id', 'users.id');

        $sql2 = $select->sqlStatement();
        $cache2 = $ref->getValue($compiler);

        $this->assertCount(1, $ref->getValue($compiler));
        $this->assertSame($sql1, $sql2);
        $this->assertSame($cache1, $cache2);
    }
}
