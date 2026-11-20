<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Tests\Traits;

use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractIndex;
use Cycle\Database\Schema\AbstractTable;

trait TableAssertions
{
    #[\Testo\Common\Attribute\AssertMethod]
    protected function assertSameAsInDB(AbstractTable $current, ?AbstractTable $target = null): void
    {
        $source = $current->getState();
        $target = $target
            ? $target->getState()
            : $this->fetchSchema($current)->getState();

        // testing changes
        \Testo\Assert::same($target->getName(), $source->getName(), 'Table name changed');

        \Testo\Assert::same($target->getPrimaryKeys(), $source->getPrimaryKeys(), 'Primary keys changed');

        \Testo\Assert::same(\count($target->getColumns()), \count($source->getColumns()), 'Column number has changed');

        \Testo\Assert::same(\count($target->getIndexes()), \count($source->getIndexes()), 'Index number has changed');

        \Testo\Assert::same(\count($target->getForeignKeys()), \count($source->getForeignKeys()), 'FK number has changed');

        // columns

        foreach ($source->getColumns() as $column) {
            \Testo\Assert::true($target->hasColumn($column->getName()), "Column {$column} has been removed");

            $this->compareColumns($column, $target->findColumn($column->getName()));
        }

        foreach ($target->getColumns() as $column) {
            \Testo\Assert::true($source->hasColumn($column->getName()), "Column {$column} has been added");

            $this->compareColumns($column, $source->findColumn($column->getName()));
        }

        // indexes

        foreach ($source->getIndexes() as $index) {
            \Testo\Assert::true($target->hasIndex($index->getColumnsWithSort()), "Index {$index->getName()} has been removed");

            $this->compareIndexes($index, $target->findIndex($index->getColumnsWithSort()));
        }

        foreach ($target->getIndexes() as $index) {
            \Testo\Assert::true($source->hasIndex($index->getColumnsWithSort()), "Index {$index->getName()} has been removed");

            $this->compareIndexes($index, $source->findIndex($index->getColumnsWithSort()));
        }

        // FK
        foreach ($source->getForeignKeys() as $key) {
            \Testo\Assert::true($target->hasForeignKey($key->getColumns()), "FK {$key->getName()} has been removed");

            $this->compareFK($key, $target->findForeignKey($key->getColumns()));
        }

        foreach ($target->getForeignKeys() as $key) {
            \Testo\Assert::true($source->hasForeignKey($key->getColumns()), "FK {$key->getName()} has been removed");

            $this->compareFK($key, $source->findForeignKey($key->getColumns()));
        }
    }

    protected function compareColumns(AbstractColumn $a, AbstractColumn $b): void
    {
        \Testo\Assert::same($b->getInternalType(), $a->getInternalType(), "Column {$a} type has been changed");

        \Testo\Assert::same($b->getScale(), $a->getScale(), "Column {$a} scale has been changed");

        \Testo\Assert::same($b->getPrecision(), $a->getPrecision(), "Column {$a} precision has been changed");

        \Testo\Assert::same($b->getEnumValues(), $a->getEnumValues(), "Column {$a} enum values has been changed");

        \Testo\Assert::true($a->compare($b), "Column {$a} has been changed");
    }

    protected function compareIndexes(AbstractIndex $a, AbstractIndex $b): void
    {
        \Testo\Assert::same($b->getColumns(), $a->getColumns(), "Index {$a->getName()} columns has been changed");

        \Testo\Assert::same($b->isUnique(), $a->isUnique(), "Index {$a->getName()} uniquness has been changed");

        \Testo\Assert::true($a->compare($b), "Index {$a->getName()} has been changed");
    }

    protected function compareFK(AbstractForeignKey $a, AbstractForeignKey $b): void
    {
        \Testo\Assert::same($b->getColumns(), $a->getColumns(), "FK {$a->getName()} column has been changed");

        \Testo\Assert::same($b->getForeignKeys(), $a->getForeignKeys(), "FK {$a->getName()} table has been changed");

        \Testo\Assert::same($b->getForeignKeys(), $a->getForeignKeys(), "FK {$a->getName()} fk has been changed");

        \Testo\Assert::same($b->getDeleteRule(), $a->getDeleteRule(), "FK {$a->getName()} delete rule has been changed");

        \Testo\Assert::same($b->getUpdateRule(), $a->getUpdateRule(), "FK {$a->getName()} update rule has been changed");

        \Testo\Assert::true($a->compare($b), "FK {$a->getName()} has been changed");
    }
}
