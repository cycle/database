<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Driver\SQLite\Schema\SQLiteIndex;
use Cycle\Database\Schema\AbstractIndex;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Test;

#[Test]
final class AbstractIndexTest
{
    #[DataSet(['email', ['email', null]], 'plain column')]
    #[DataSet(['email ASC', ['email', 'ASC']], 'ascending')]
    #[DataSet(['email DESC', ['email', 'DESC']], 'descending')]
    #[DataSet([['email', 'DESC'], ['email', 'DESC']], 'already parsed')]
    public function parseColumnSplitsTheSortOrder(string|array $expression, array $expected): void
    {
        Assert::same(AbstractIndex::parseColumn($expression), $expected);
    }

    public function columnsAcceptsVariadicNames(): void
    {
        $index = new SQLiteIndex('users', 'users_index');

        $index->columns('last_name', 'first_name');

        Assert::same($index->getColumns(), ['last_name', 'first_name']);
    }

    public function sortIsKeptPerColumn(): void
    {
        $index = (new SQLiteIndex('users', 'users_index'))->columns(['last_name', 'age']);

        $index->sort(['age' => 'DESC']);

        Assert::same($index->getSort(), ['age' => 'DESC']);
        Assert::same($index->getColumnsWithSort(), ['last_name', 'age DESC']);
    }
}
