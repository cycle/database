<?php
/**
 * Spiral, Core Components
 *
 * @author    Dmitry Mironov <dmitry.mironov@spiralscout.com>
 */

namespace Spiral\Tests\Pagination\Traits;


use Interop\Container\ContainerInterface;
use Spiral\Core\Exceptions\ScopeException;
use Spiral\Pagination\Exceptions\PaginationException;
use Spiral\Pagination\Paginator;
use Spiral\Pagination\PaginatorsInterface;
use Spiral\Pagination\Traits\PaginatorTrait;

/**
 * Class PaginatorTraitTest
 *
 * @package Spiral\Tests\Pagination\Traits
 */
class PaginatorTraitTest extends \PHPUnit_Framework_TestCase
{
    const PAGINATOR_LIMIT     = 10;
    const PAGINATOR_COUNT     = 15;
    const PAGINATOR_PARAMETER = 'test';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PaginatorTrait
     */
    private $trait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Paginator
     */
    private $paginator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->trait = $this->getMockForTrait(PaginatorTrait::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->paginator = $this->createMock(Paginator::class);

        $this->trait->method('iocContainer')->willReturn($this->container);
    }

    public function testSetPaginator()
    {
        $this->assertFalse($this->trait->hasPaginator());
        $this->assertEquals($this->trait, $this->trait->setPaginator($this->paginator));
        $this->assertTrue($this->trait->hasPaginator());
        $this->assertEquals($this->paginator, $this->trait->getPaginator());
    }

    public function testGetPaginatorWasNotSetException()
    {
        $this->expectException(PaginationException::class);
        $this->trait->getPaginator();
    }

    public function testPaginate()
    {
        $paginators = $this->createMock(PaginatorsInterface::class);
        $paginators->method('createPaginator')
            ->with(static::PAGINATOR_PARAMETER, static::PAGINATOR_LIMIT)
            ->willReturn($this->paginator);

        $this->container->method('has')->with(PaginatorsInterface::class)->willReturn(true);
        $this->container->method('get')->with(PaginatorsInterface::class)->willReturn($paginators);

        $this->assertEquals($this->trait,
            $this->trait->paginate(static::PAGINATOR_LIMIT, static::PAGINATOR_PARAMETER));
    }

    public function testPaginateScopeExceptionNoContainer()
    {
        $this->expectException(ScopeException::class);
        $this->trait->paginate();
    }

    public function testConfigurePaginator()
    {
        $reflection = new \ReflectionObject($this->trait);
        $method = $reflection->getMethod('configurePaginator');
        $method->setAccessible(true);

        $this->trait->setPaginator($this->paginator);
        $this->assertNotSame($this->paginator, $method->invoke($this->trait));
        $this->assertNotSame($this->paginator,
            $method->invoke($this->trait, self::PAGINATOR_COUNT));
    }
}