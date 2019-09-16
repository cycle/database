<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;

class ParameterTest extends TestCase
{
    public function testValue()
    {
        $parameter = new Parameter('value');
        $this->assertInstanceOf(ParameterInterface::class, $parameter);

        $this->assertSame('value', $parameter->getValue());

        $newParameter = $parameter->withValue('new value');
        $this->assertNotSame($parameter, $newParameter);

        $this->assertSame('new value', $newParameter->getValue());
    }

    public function testType()
    {
        $parameter = new Parameter(123, \PDO::PARAM_INT);

        $this->assertSame(123, $parameter->getValue());
        $this->assertSame(\PDO::PARAM_INT, $parameter->getType());

        $newParameter = $parameter->withValue(2334);
        $this->assertNotSame($parameter, $newParameter);

        $this->assertSame(2334, $newParameter->getValue());
        $this->assertSame(\PDO::PARAM_STR, $newParameter->getType());
    }

    public function testAutoTyping()
    {
        $parameter = new Parameter('abc');
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());

        $parameter = new Parameter(123);
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());

        $parameter = new Parameter(null);
        $this->assertSame(\PDO::PARAM_NULL, $parameter->getType());

        $parameter = new Parameter(false);
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());

        $parameter = new Parameter(true);
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());
    }

    /**
     * At this moment arrays values are always treated as STMP parameter.
     */
    public function testAutoTypingArrays()
    {
        $parameter = new Parameter([1, 2, 3]);
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());

        $parameter = new Parameter(['1', '2', '3']);
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());
    }

    public function testDebugInfo()
    {
        $parameter = new Parameter([1, 2, 3]);

        $this->assertSame([
            'value'     => [1, 2, 3],
            'type'      => \PDO::PARAM_STR
        ], $parameter->__debugInfo());
    }
}
