<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Migrations;

abstract class MigratorTest extends BaseTest
{
    public function testConfigure()
    {
        $this->assertFalse($this->migrator->isConfigured());

        $this->migrator->configure();
        $this->assertTrue($this->db->hasTable('migrations'));
    }

    //no errors expected
    public function testConfigureTwice()
    {
        $this->assertFalse($this->migrator->isConfigured());

        $this->migrator->configure();
        $this->assertTrue($this->db->hasTable('migrations'));

        $this->migrator->configure();
    }

    public function testGetEmptyMigrations()
    {
        $this->migrator->configure();
        $this->assertSame([], $this->migrator->getMigrations());
    }

    public function testRepository()
    {
        $this->assertSame($this->repository, $this->migrator->getRepository());
    }

    /**
     * @expectedException \Spiral\Migrations\Exceptions\MigrationException
     */
    public function testRunUnconfigured()
    {
        $this->migrator->run();
    }

    /**
     * @expectedException \Spiral\Migrations\Exceptions\MigrationException
     */
    public function testRollbackUnconfigured()
    {
        $this->migrator->rollback();
    }
}