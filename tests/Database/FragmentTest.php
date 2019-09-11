<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Database\Driver\DriverInterface;
use Spiral\Database\Driver\MySQL\MySQLCompiler;
use Spiral\Database\Driver\QueryBindings;
use Spiral\Database\Driver\Quoter;
use Spiral\Database\Injection\Fragment;
use Spiral\Database\Injection\FragmentInterface;

class FragmentTest extends TestCase
{
    public function testFragment()
    {
        $fragment = new Fragment('some sql');
        $this->assertInstanceOf(FragmentInterface::class, $fragment);

        $this->assertSame('some sql', $fragment->compile(
            new QueryBindings(),
            new MySQLCompiler(
                new Quoter(m::mock(DriverInterface::class), "")
            )
        ));
    }

    public function testSerialize()
    {
        $fragment = new Fragment('some sql');
        $fragment2 = Fragment::__set_state(['statement' => 'some sql']);

        $this->assertEquals($fragment2, $fragment);
    }
}
