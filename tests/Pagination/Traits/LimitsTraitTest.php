<?php
/**
 * Spiral, Core Components
 *
 * @author    Dmitry Mironov <dmitry.mironov@spiralscout.com>
 */

namespace Spiral\Tests\Pagination\Traits;


use Spiral\Pagination\Traits\LimitsTrait;

/**
 * Class LimitTraitTest
 *
 * @package Spiral\Tests\Pagination\Traits
 */
class LimitsTraitTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_LIMIT  = 0;
    const DEFAULT_OFFSET = 0;
    const LIMIT          = 10;
    const OFFSET         = 15;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LimitsTrait
     */
    private $trait;

    public function setUp()
    {
        $this->trait = $this->getMockForTrait(LimitsTrait::class);
    }

    public function testLimit()
    {
        $this->assertEquals(static::DEFAULT_LIMIT, $this->trait->getLimit());
        $this->assertEquals($this->trait, $this->trait->limit(static::LIMIT));
        $this->assertEquals(static::LIMIT, $this->trait->getLimit());
    }

    public function testOffset()
    {
        $this->assertEquals(static::DEFAULT_OFFSET, $this->trait->getOffset());
        $this->assertEquals($this->trait, $this->trait->offset(static::OFFSET));
        $this->assertEquals(static::OFFSET, $this->trait->getOffset());
    }
}