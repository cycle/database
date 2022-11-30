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
    protected function assertSameAsInDB(AbstractTable $current, ?AbstractTable $target = null): void
    {
        $source = $current->getState();
        $target = $target
            ? $target->getState()
            : $this->fetchSchema($current)->getState();

        // testing changes
        $this->assertSame(
            $source->getName(),
            $target->getName(),
            'Table name changed'
        );

        $this->assertSame(
            $source->getPrimaryKeys(),
            $target->getPrimaryKeys(),
            'Primary keys changed'
        );

        $this->assertSame(
            count($source->getColumns()),
            count($target->getColumns()),
            'Column number has changed'
        );

        $this->assertSame(
            count($source->getIndexes()),
            count($target->getIndexes()),
            'Index number has changed'
        );

        $this->assertSame(
            count($source->getForeignKeys()),
            count($target->getForeignKeys()),
            'FK number has changed'
        );

        // columns

        foreach ($source->getColumns() as $column) {
            $this->assertTrue(
                $target->hasColumn($column->getName()),
                "Column {$column} has been removed"
            );

            $this->compareColumns($column, $target->findColumn($column->getName()));
        }

        foreach ($target->getColumns() as $column) {
            $this->assertTrue(
                $source->hasColumn($column->getName()),
                "Column {$column} has been added"
            );

            $this->compareColumns($column, $source->findColumn($column->getName()));
        }

        // indexes

        foreach ($source->getIndexes() as $index) {
            $this->assertTrue(
                $target->hasIndex($index->getColumnsWithSort()),
                "Index {$index->getName()} has been removed"
            );

            $this->compareIndexes($index, $target->findIndex($index->getColumnsWithSort()));
        }

        foreach ($target->getIndexes() as $index) {
            $this->assertTrue(
                $source->hasIndex($index->getColumnsWithSort()),
                "Index {$index->getName()} has been removed"
            );

            $this->compareIndexes($index, $source->findIndex($index->getColumnsWithSort()));
        }

        // FK
        foreach ($source->getForeignKeys() as $key) {
            $this->assertTrue(
                $target->hasForeignKey($key->getColumns()),
                "FK {$key->getName()} has been removed"
            );

            $this->compareFK($key, $target->findForeignKey($key->getColumns()));
        }

        foreach ($target->getForeignKeys() as $key) {
            $this->assertTrue(
                $source->hasForeignKey($key->getColumns()),
                "FK {$key->getName()} has been removed"
            );

            $this->compareFK($key, $source->findForeignKey($key->getColumns()));
        }
    }

    protected function compareColumns(AbstractColumn $a, AbstractColumn $b): void
    {
        $this->assertSame(
            \strtolower($a->getInternalType()),
            \strtolower($b->getInternalType()),
            "Column {$a} type has been changed"
        );

        $this->assertSame(
            $a->getScale(),
            $b->getScale(),
            "Column {$a} scale has been changed"
        );

        $this->assertSame(
            $a->getPrecision(),
            $b->getPrecision(),
            "Column {$a} precision has been changed"
        );

        $this->assertSame(
            $a->getEnumValues(),
            $b->getEnumValues(),
            "Column {$a} enum values has been changed"
        );

        $this->assertTrue(
            $a->compare($b),
            "Column {$a} has been changed"
        );
    }

    protected function compareIndexes(AbstractIndex $a, AbstractIndex $b): void
    {
        $this->assertSame(
            $a->getColumns(),
            $b->getColumns(),
            "Index {$a->getName()} columns has been changed"
        );

        $this->assertSame(
            $a->isUnique(),
            $b->isUnique(),
            "Index {$a->getName()} uniquness has been changed"
        );

        $this->assertTrue(
            $a->compare($b),
            "Index {$a->getName()} has been changed"
        );
    }

    protected function compareFK(AbstractForeignKey $a, AbstractForeignKey $b): void
    {
        $this->assertSame(
            $a->getColumns(),
            $b->getColumns(),
            "FK {$a->getName()} column has been changed"
        );

        $this->assertSame(
            $a->getForeignKeys(),
            $b->getForeignKeys(),
            "FK {$a->getName()} table has been changed"
        );

        $this->assertSame(
            $a->getForeignKeys(),
            $b->getForeignKeys(),
            "FK {$a->getName()} fk has been changed"
        );

        $this->assertSame(
            $a->getDeleteRule(),
            $b->getDeleteRule(),
            "FK {$a->getName()} delete rule has been changed"
        );

        $this->assertSame(
            $a->getUpdateRule(),
            $b->getUpdateRule(),
            "FK {$a->getName()} update rule has been changed"
        );

        $this->assertTrue(
            $a->compare($b),
            "FK {$a->getName()} has been changed"
        );
    }
}
