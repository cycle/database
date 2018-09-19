<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Database\Tests;

use Spiral\Database\Injection\ExpressionInterface;
use Spiral\Database\Injection\FragmentInterface;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;

class ParameterTest extends \PHPUnit_Framework_TestCase
{
    public function testValue()
    {
        $parameter = new Parameter('value');
        $this->assertInstanceOf(FragmentInterface::class, $parameter);
        $this->assertInstanceOf(ParameterInterface::class, $parameter);
        $this->assertNotInstanceOf(ExpressionInterface::class, $parameter);

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
        $this->assertSame(\PDO::PARAM_INT, $newParameter->getType());
    }

    public function testPlaceholders()
    {
        $parameter = new Parameter(123, \PDO::PARAM_INT);

        $this->assertSame('?', $parameter->sqlStatement());
        $this->assertSame($parameter->sqlStatement(), (string)$parameter);
    }

    public function testPlaceholdersArray()
    {
        $parameter = new Parameter([1, 2, 3], \PDO::PARAM_INT);

        $this->assertSame('(?, ?, ?)', $parameter->sqlStatement());
        $this->assertSame($parameter->sqlStatement(), (string)$parameter);
    }

    public function testFlattenScalar()
    {
        $parameter = new Parameter(123, \PDO::PARAM_INT);

        $flattened = $parameter->flatten();
        $this->assertCount(1, $flattened);

        foreach ($flattened as $subParameter) {
            $this->assertInstanceOf(ParameterInterface::class, $subParameter);
        }

        $this->assertSame(123, $flattened[0]->getValue());
        $this->assertNotSame($parameter, $flattened[0]);
    }

    public function testFlattenArray()
    {
        $parameter = new Parameter([1, 2, 3], \PDO::PARAM_INT);

        $flattened = $parameter->flatten();
        $this->assertCount(3, $flattened);

        foreach ($flattened as $subParameter) {
            $this->assertInstanceOf(ParameterInterface::class, $subParameter);
            $this->assertSame(\PDO::PARAM_INT, $subParameter->getType());
        }

        $this->assertSame(1, $flattened[0]->getValue());
        $this->assertSame(2, $flattened[1]->getValue());
        $this->assertSame(3, $flattened[2]->getValue());
    }

    public function testAutoTyping()
    {
        $parameter = new Parameter('abc');
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());

        $parameter = new Parameter(123);
        $this->assertSame(\PDO::PARAM_INT, $parameter->getType());

        $parameter = new Parameter(null);
        $this->assertSame(\PDO::PARAM_NULL, $parameter->getType());

        $parameter = new Parameter(false);
        $this->assertSame(\PDO::PARAM_BOOL, $parameter->getType());

        $parameter = new Parameter(true);
        $this->assertSame(\PDO::PARAM_BOOL, $parameter->getType());
    }

    /**
     * At this moment arrays values are always treated as STMP parameter.
     */
    public function testAutoTypingArrays()
    {
        $parameter = new Parameter([1, 2, 3]);
        $this->assertSame(\PDO::PARAM_STMT, $parameter->getType());

        $parameter = new Parameter(['1', '2', '3']);
        $this->assertSame(\PDO::PARAM_STMT, $parameter->getType());
    }

    public function testDebugInfo()
    {
        $parameter = new Parameter([1, 2, 3]);

        $this->assertSame([
            'statement' => '(?, ?, ?)',
            'value'     => [1, 2, 3],
            'type'      => \PDO::PARAM_STMT
        ], $parameter->__debugInfo());
    }
}