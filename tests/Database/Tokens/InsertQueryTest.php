<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Tokens;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Driver\CompilerInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\InsertQuery;

class InsertQueryTest extends TestCase
{
    public function testBuildQuery(): void
    {
        $insert = new InsertQuery();
        $insert
            ->into('table')
            ->columns('name', 'value')
            ->values(['Antony', 1]);

        $this->assertSame(
            CompilerInterface::INSERT_QUERY,
            $insert->getType()
        );

        $this->assertEquals(
            [
                'table'   => 'table',
                'columns' => ['name', 'value'],
                'values'  => [new Parameter(['Antony', 1])]
            ],
            $insert->getTokens()
        );
    }
}
