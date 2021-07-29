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
use Cycle\Database\Query\DeleteQuery;

class DeleteQueryTest extends TestCase
{
    public function testBuildQuery(): void
    {
        $delete = new DeleteQuery();
        $delete
            ->from('table')
            ->where(['name' => 'Antony'])
            ->orWhere('id', '>', 1);

        $this->assertSame(
            CompilerInterface::DELETE_QUERY,
            $delete->getType()
        );

        $this->assertEquals(
            [
                'table' => 'table',
                'where' => [
                    [
                        'AND',
                        ['name', '=', new Parameter('Antony')]
                    ],
                    [
                        'OR',
                        ['id', '>', new Parameter(1)]
                    ],
                ]
            ],
            $delete->getTokens()
        );
    }
}
