<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
namespace Spiral\Tests\Database\MySQL;

/**
 * MySQL 5.6 and higher
 */
class DatetimeColumns56Test extends \Spiral\Tests\Database\DatetimeColumnsTest
{
    use DriverTrait;

    public function setUp()
    {
        parent::setUp();
        $pdo = $this->database->getDriver()->getPDO();

        $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

        if (version_compare('5.6', $version, '>=')) {
            $this->markTestSkipped('TestCase is specific to 5.6+ drivers only');
        }
    }

    /**
     * @expectedException \Spiral\Database\Exceptions\SchemaHandlerException
     * @expectedExceptionMessage SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid
     *                           default value for 'target'
     */
    public function testTimestampDatetimeZero()
    {
        parent::testTimestampDatetimeZero();
    }
}