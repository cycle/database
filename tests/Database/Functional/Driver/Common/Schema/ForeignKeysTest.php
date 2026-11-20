<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Functional\Driver\Common\Schema;

use Cycle\Database\Driver\Handler;
use Cycle\Database\Schema\AbstractColumn;
use Cycle\Database\Schema\AbstractForeignKey;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Database\Tests\Functional\Driver\Common\BaseTest;

abstract class ForeignKeysTest extends BaseTest
{
    public function sampleSchema(string $table): AbstractTable
    {
        $schema = $this->schema($table);

        if (!$schema->exists()) {
            $schema->primary('id');

            $schema->integer('secondary_id');
            $schema->index(['secondary_id'])->unique(true); //Index is required

            $schema->integer('secondary_id_2');
            $schema->integer('secondary_id_3');
            $schema->index(['secondary_id_2', 'secondary_id_3'])->unique(true); //Index is required

            $schema->string('first_name')->nullable(false);
            $schema->string('last_name')->nullable(false);
            $schema->string('email', 64)->nullable(false);
            $schema->enum('status', ['active', 'disabled'])->defaultValue('active');
            $schema->double('balance')->defaultValue(0);
            $schema->boolean('flagged')->defaultValue(true);

            $schema->text('bio');

            //Some dates
            $schema->timestamp('timestamp')->defaultValue(AbstractColumn::DATETIME_NOW);
            $schema->datetime('datetime')->defaultValue('2017-01-01 00:00:00');
            $schema->date('datetime')->nullable(true);
            $schema->time('datetime')->defaultValue('00:00');

            $schema->save(Handler::DO_ALL);
        }

        return $schema;
    }

    #[\Testo\Test]
    public function testCreateWithReferenceToExistedTable(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('external', ['id']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));
    }

    #[\Testo\Test]
    public function testCreateWithReferenceToExistedTableWithName(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('external', ['id']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));
    }

    #[\Testo\Test]
    public function testCreateWithReferenceToExistedTableCascade(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::CASCADE)
            ->onUpdate(AbstractForeignKey::CASCADE);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));
    }

    #[\Testo\Test]
    public function testCreateWithReferenceToExistedTableNoAction(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));
    }

    #[\Testo\Test]
    public function testDropExistedReference(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->dropForeignKey(['external_id']);
        $schema->save(Handler::DO_ALL);

        \Testo\Assert::false($this->schema('schema')->hasForeignKey(['external_id']));
    }

    #[\Testo\Test]
    public function testChangeReferenceForeignKey(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->references('external', ['secondary_id']);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeReferenceForeignTable(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));

        \Testo\Assert::true($this->sampleSchema('external2')->exists());

        $schema->foreignKey(['external_id'])->references('external2', ['secondary_id']);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeUpdateRuleToCascade(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->onUpdate(AbstractForeignKey::CASCADE);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeUpdateRuleToNoAction(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::CASCADE)
            ->onUpdate(AbstractForeignKey::CASCADE);

        $schema->save(Handler::DO_ALL);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->onUpdate(AbstractForeignKey::NO_ACTION);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeDeleteRuleToCascade(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::NO_ACTION)
            ->onUpdate(AbstractForeignKey::NO_ACTION);

        $schema->save(Handler::DO_ALL);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->onDelete(AbstractForeignKey::CASCADE);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testChangeDeleteRuleToNoAction(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');

        $schema->foreignKey(['external_id'])->references('external', ['id'])
            ->onDelete(AbstractForeignKey::CASCADE)
            ->onUpdate(AbstractForeignKey::CASCADE);

        $schema->save(Handler::DO_ALL);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));

        $schema->foreignKey(['external_id'])->onDelete(AbstractForeignKey::NO_ACTION);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testCompositeKeys(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->integer('external_id_2');

        $schema
            ->foreignKey(['external_id', 'external_id_2'])
            ->references('external', ['secondary_id_2', 'secondary_id_3'])
            ->onDelete(AbstractForeignKey::CASCADE)
            ->onUpdate(AbstractForeignKey::CASCADE);

        $schema->save(Handler::DO_ALL);

        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id', 'external_id_2']));

        $schema->foreignKey(['external_id', 'external_id_2'])->onDelete(AbstractForeignKey::NO_ACTION);
        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
    }

    #[\Testo\Test]
    public function testCreateWithIndex(): void
    {
        $schema = $this->schema('schema');
        \Testo\Assert::false($schema->exists());
        \Testo\Assert::true($this->sampleSchema('external')->exists());

        $schema->primary('id');
        $schema->integer('external_id');
        $schema->foreignKey(['external_id'])->references('external', ['id']);

        $schema->save(Handler::DO_ALL);

        $this->assertSameAsInDB($schema);
        \Testo\Assert::true($this->schema('schema')->hasForeignKey(['external_id']));
        \Testo\Assert::true($this->schema('schema')->hasIndex(['external_id']));
    }
}
