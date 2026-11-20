<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit;

use Cycle\Database\Driver\SQLServer\Schema\SQLServerForeignKey;
use Cycle\Database\Query\QueryParameters;
use Testo\Assert;
use Testo\Test;

/**
 * Class names of the former `spiral/database` package keep resolving to their `cycle/database` classes.
 */
#[Test]
final class LegacyNamespaceTest
{
    public function legacyClassNameAliasesTheCycleClass(): void
    {
        \error_clear_last();

        $exists = \class_exists('Spiral\Database\Query\QueryParameters');

        Assert::true($exists);
        Assert::same((new \ReflectionClass('Spiral\Database\Query\QueryParameters'))->getName(), QueryParameters::class);
        Assert::same(\error_get_last()['type'] ?? null, \E_USER_DEPRECATED);
        Assert::string(\error_get_last()['message'])->contains('use class ' . QueryParameters::class . ' instead');
    }

    public function misspelledSqlServerForeignKeyIsAliased(): void
    {
        Assert::same(
            (new \ReflectionClass('Spiral\Database\Driver\SQLServer\Schema\SQlServerForeignKey'))->getName(),
            SQLServerForeignKey::class,
        );
    }

    public function otherNamespacesAreLeftAlone(): void
    {
        Assert::false(\class_exists('Spiral\Other\Query\QueryParameters'));
    }
}
