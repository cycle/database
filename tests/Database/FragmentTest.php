<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Injection\Fragment;
use Spiral\Database\Injection\FragmentInterface;

class FragmentTest extends TestCase
{
    public function testFragment()
    {
        $fragment = new Fragment('some sql');
        $this->assertInstanceOf(FragmentInterface::class, $fragment);

        $this->assertSame('some sql', $fragment->sqlStatement());
        $this->assertSame($fragment->sqlStatement(), (string)$fragment);
    }

    public function testDebugInfo()
    {
        $fragment = new Fragment('some sql');

        $this->assertSame([
            'statement' => $fragment->sqlStatement()
        ], $fragment->__debugInfo());
    }

    public function testSerialize()
    {
        $fragment = new Fragment('some sql');
        $fragment2 = unserialize(serialize($fragment));

        $this->assertEquals($fragment2, $fragment);
    }
}