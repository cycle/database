<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Schema;

use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Database;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\Database\Exception\SchemaException;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Schema\State;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
final class AbstractTableTest
{
    private Database $db;

    #[BeforeTest]
    public function connect(): void
    {
        $this->db = new Database('default', '', SQLiteDriver::create(new SQLiteDriverConfig()));
    }

    public function sanitizesAColumnExpression(): void
    {
        Assert::same(AbstractTable::sanitizeColumnExpression('LOWER(Name) x'), 'lower__name____x');
    }

    public function listsTheTableOfEveryForeignKey(): void
    {
        $table = $this->table('t');
        $table->integer('a');
        $table->integer('b');
        $table->foreignKey(['a'])->references('x', ['id']);
        $table->foreignKey(['b'])->references('y', ['id']);

        Assert::same($table->getDependencies(), ['x', 'y']);
    }

    public function namesAnIndexAfterTheTableAndItsColumns(): void
    {
        $table = $this->table('t');
        $table->integer('a');
        $table->integer('b');

        Assert::string($table->index(['a', 'b'])->getName())->matchesRegex('/^t_index_a_b_[0-9a-f]{13}$/');
    }

    public function keepsAnIndexNameOf64Characters(): void
    {
        $table = $this->table('t');
        $column = \str_repeat('c', 42);
        $table->integer($column);

        $name = $table->index([$column])->getName();

        Assert::string($name)->startsWith("t_index_{$column}_");
        Assert::same(\strlen($name), 64);
    }

    public function hashesAnIndexNameOver64Characters(): void
    {
        $table = $this->table('t');
        $column = \str_repeat('c', 43);
        $table->integer($column);

        Assert::string($table->index([$column])->getName())->matchesRegex('/^[0-9a-f]{32}$/');
    }

    public function rejectsAnIndexOnAMissingColumn(): never
    {
        $table = $this->table('t');
        $table->integer('a');

        Expect::exception(SchemaException::class)->withMessage("Undefined column 'b' in 't'");

        $table->index(['a', 'b']);
    }

    public function rejectsAForeignKeyOnAMissingColumn(): never
    {
        $table = $this->table('t');
        $table->integer('a');

        Expect::exception(SchemaException::class)->withMessage("Undefined column 'b' in 't'");

        $table->foreignKey(['a', 'b']);
    }

    public function createsNoIndexForAForeignKeyWhenAskedNotTo(): void
    {
        $table = $this->table('t');
        $table->integer('a');

        $foreignKey = $table->foreignKey(['a'], false);

        Assert::false($foreignKey->hasIndex());
        Assert::false($table->hasIndex(['a']));
    }

    public function rejectsRenamingAMissingColumn(): never
    {
        Expect::exception(SchemaException::class)->withMessage("Undefined column 'a' in 't'");

        $this->table('t')->renameColumn('a', 'b');
    }

    public function rejectsRenamingAMissingIndex(): never
    {
        Expect::exception(SchemaException::class)->withMessage("Undefined index ['a'] in 't'");

        $this->table('t')->renameIndex(['a'], 'b');
    }

    public function rejectsDroppingAMissingColumn(): never
    {
        Expect::exception(SchemaException::class)->withMessage("Undefined column 'a' in 't'");

        $this->table('t')->dropColumn('a');
    }

    public function rejectsDroppingAMissingIndex(): never
    {
        Expect::exception(SchemaException::class)->withMessage("Undefined index ['a'] in 't'");

        $this->table('t')->dropIndex(['a']);
    }

    public function dropsADeclaredColumn(): void
    {
        $table = $this->table('t');
        $table->integer('a');

        $table->dropColumn('a');

        Assert::false($table->hasColumn('a'));
    }

    public function dropsADeclaredIndex(): void
    {
        $table = $this->table('t');
        $table->integer('a');
        $table->index(['a']);

        $table->dropIndex(['a']);

        Assert::false($table->hasIndex(['a']));
    }

    public function returnsADetachedStateKeyedByTheCurrentNames(): void
    {
        $table = $this->table('t');
        $table->integer('a');
        $table->renameColumn('a', 'b');

        $state = $table->getState();
        $state->forgetColumn($state->findColumn('b'));

        Assert::same(\array_keys($table->getState()->getColumns()), ['b']);
    }

    public function takesTheNameOfANewState(): void
    {
        $table = $this->table('t');

        $table->setState(new State('other'));

        Assert::same($table->getName(), 'other');
    }

    public function resetsToTheInitialState(): void
    {
        $table = $this->table('t');
        $table->integer('a');

        $table->resetState();

        Assert::false($table->hasColumn('a'));
    }

    public function aCloneSavesWithoutTouchingTheOriginal(): void
    {
        $table = $this->table('t');
        $table->integer('a');
        $copy = clone $table;

        $copy->save();

        $added = \array_map(static fn($column): string => $column->getName(), $table->getComparator()->addedColumns());

        Assert::same(\array_values($added), ['a']);
    }

    public function describesItselfForDebugging(): void
    {
        $table = $this->table('t');
        $table->primary('id');
        $table->integer('a');
        $table->index(['a']);

        $info = $table->__debugInfo();

        Assert::same(\array_keys($info), ['status', 'full_name', 'name', 'primaryKeys', 'columns', 'indexes', 'foreignKeys']);
        Assert::same($info['status'], AbstractTable::STATUS_NEW);
        Assert::same($info['full_name'], 't');
        Assert::same($info['name'], 't');
        Assert::same($info['primaryKeys'], ['id']);
        Assert::array($info['columns'])->isList()->hasCount(2);
        Assert::array($info['indexes'])->isList()->hasCount(1);
        Assert::array($info['foreignKeys'])->isList()->hasCount(0);
    }

    public function dropsTheIndexesOfADroppedColumn(): void
    {
        $table = $this->table('t');
        $table->primary('id');
        $table->integer('a');
        $table->integer('b');
        $table->index(['a']);
        $table->save();

        $table->dropColumn('a');
        $table->save();

        Assert::false($this->table('t')->hasIndex(['a']));
        Assert::false($this->table('t')->hasColumn('a'));
    }

    public function keepsTheIndexOfARenamedColumn(): void
    {
        $table = $this->table('t');
        $table->primary('id');
        $table->integer('a');
        $table->index(['a']);
        $table->save();

        $table->renameColumn('a', 'b');
        $table->save();

        Assert::true($this->table('t')->hasIndex(['b']));
        Assert::false($this->table('t')->hasIndex(['a']));
    }

    public function keepsTheForeignKeyOfARenamedColumn(): void
    {
        $parent = $this->table('p');
        $parent->primary('id');
        $parent->save();
        $table = $this->table('t');
        $table->primary('id');
        $table->integer('a');
        $table->foreignKey(['a'])->references('p', ['id']);
        $table->save();

        $table->renameColumn('a', 'b');
        $table->save();

        Assert::true($this->table('t')->hasForeignKey(['b']));
    }

    public function leavesForeignKeysOutWhenTheirCreationIsNotAsked(): void
    {
        $parent = $this->table('p');
        $parent->primary('id');
        $parent->save();
        $table = $this->table('t');
        $table->primary('id');
        $table->integer('a');
        $table->foreignKey(['a'])->references('p', ['id']);

        $table->save(HandlerInterface::DO_ALL ^ HandlerInterface::CREATE_FOREIGN_KEYS);

        Assert::false($this->table('t')->hasForeignKey(['a']));
    }

    private function table(string $name): AbstractTable
    {
        return $this->db->table($name)->getSchema();
    }
}
