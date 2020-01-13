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
use Spiral\Database\Query\DeleteQuery;

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
