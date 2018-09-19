<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Database\Tests\MySQL56;

/**
 * MySQL 5.6 and higher
 */
class DatetimeColumnsTest extends \Spiral\Database\Tests\DatetimeColumnsTest
{
    const DRIVER = 'mysql56';

    public function setUp()
    {
        parent::setUp();

        $version = $this->database->getDriver()->getPDO()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        if (version_compare('5.6', $version, '>=')) {
            $this->markTestSkipped('TestCase is specific to 5.6+ drivers only');
        }
    }

    /**
     * @expectedException \Spiral\Database\Exception\SchemaHandlerException
     * @expectedExceptionMessage SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid
     *                           default value for 'target'
     */
    public function testTimestampDatetimeZero()
    {
        parent::testTimestampDatetimeZero();
    }
}