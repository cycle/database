<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Tokens;

use PHPUnit\Framework\TestCase;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;

class SelectQueryTest extends TestCase
{
    public function testBuildQuery(): void
    {
        $select = new SelectQuery();
        $select
            ->from('table')
            ->columns('name', 'value')
            ->where(['name' => 'Antony'])
            ->orWhere('id', '>', 1);

        $this->assertSame(
            CompilerInterface::SELECT_QUERY,
            $select->getType()
        );

        $this->assertEquals(
            [
                'forUpdate' => false,
                'from' => ['table'],
                'join' => [],
                'columns' => ['name', 'value'],
                'distinct' => false,
                'where' => [
                    [
                        'AND',
                        ['name', '=', new Parameter('Antony')],
                    ],
                    [
                        'OR',
                        ['id', '>', new Parameter(1)],
                    ],
                ],
                'having' => [],
                'groupBy' => [],
                'orderBy' => [],
                'limit' => null,
                'offset' => null,
                'union' => [],
            ],
            $select->getTokens()
        );
    }
}
