<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query\Tokens;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\DeleteQuery;
use Testo\Assert;
use Testo\Test;

#[Test]
final class DeleteQueryTest
{
    public function testBuildQuery(): void
    {
        $delete = new DeleteQuery();
        $delete
            ->from('table')
            ->where(['name' => 'Antony'])
            ->orWhere('id', '>', 1);

        Assert::same($delete->getType(), CompilerInterface::DELETE_QUERY);

        Assert::equals($delete->getTokens(), [
            'table' => 'table',
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
        ]);
    }
}
