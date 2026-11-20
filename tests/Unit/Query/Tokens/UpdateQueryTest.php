<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Query\Tokens;

use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\UpdateQuery;
use Testo\Assert;
use Testo\Test;

#[Test]
final class UpdateQueryTest
{
    public function testBuildQuery(): void
    {
        $delete = new UpdateQuery();
        $delete
            ->in('table')
            ->set('name', 'John')
            ->where(['name' => 'Antony'])
            ->orWhere('id', '>', 1);

        Assert::same($delete->getType(), CompilerInterface::UPDATE_QUERY);

        Assert::equals($delete->getTokens(), [
            'table' => 'table',
            'values' => [
                'name' => 'John',
            ],
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
