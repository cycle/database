<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Injections\Fragment;
use Spiral\Database\Injections\FragmentInterface;

class FragmentTest extends \PHPUnit_Framework_TestCase
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
}