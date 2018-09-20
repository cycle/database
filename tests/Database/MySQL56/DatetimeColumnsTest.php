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

    /**
     * @expectedException \Spiral\Database\Exception\HandlerException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1293 Incorrect table definition;
     *                           there can be only one TIMESTAMP column with CURRENT_TIMESTAMP in
     *                           DEFAULT or ON UPDATE clause
     */
    public function testMultipleTimestampCurrentTimestamp()
    {
        parent::testMultipleTimestampCurrentTimestamp();
    }

    /**
     * @expectedException \Spiral\Database\Exception\HandlerException
     * @expectedExceptionMessage SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid
     *                           default value for 'target'
     */
    public function testMultipleDatetimeCurrentTimestamp()
    {
        parent::testMultipleDatetimeCurrentTimestamp();
    }

    /**
     * @expectedException \Spiral\Database\Exception\HandlerException
     * @expectedExceptionMessage SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid
     *                           default value for 'target'
     */
    public function testTimestampDatetimeZero()
    {
        parent::testTimestampDatetimeZero();
    }

    /**
     * @expectedException \Spiral\Database\Exception\HandlerException
     * @expectedExceptionMessage SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid
     *                           default value for 'target'
     */
    public function testDatetimeCurrentTimestamp()
    {
        parent::testDatetimeCurrentTimestamp();
    }

    /**
     * @expectedException \Spiral\Database\Exception\HandlerException
     * @expectedExceptionMessage SQLSTATE[42000]: Syntax error or access violation: 1067 Invalid
     *                           default value for 'target'
     */
    public function testDatetimeCurrentTimestampNotNull()
    {
        parent::testDatetimeCurrentTimestampNotNull();
    }
}