<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Injection\ParameterInterface;

class ParameterTest extends TestCase
{
    public function testValue(): void
    {
        $parameter = new Parameter('value');
        $this->assertInstanceOf(ParameterInterface::class, $parameter);

        $this->assertSame('value', $parameter->getValue());

        $parameter->setValue('new value');

        $this->assertSame('new value', $parameter->getValue());
    }

    public function testType(): void
    {
        $parameter = new Parameter(123, \PDO::PARAM_INT);

        $this->assertSame(123, $parameter->getValue());
        $this->assertSame(\PDO::PARAM_INT, $parameter->getType());

        $parameter->setValue(2334);

        $this->assertSame(2334, $parameter->getValue());
        $this->assertSame(\PDO::PARAM_INT, $parameter->getType());
    }

    public function testAutoTyping(): void
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
    public function testAutoTypingArrays(): void
    {
        $parameter = new Parameter([1, 2, 3]);
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());

        $parameter = new Parameter(['1', '2', '3']);
        $this->assertSame(\PDO::PARAM_STR, $parameter->getType());
    }

    public function testDebugInfo(): void
    {
        $parameter = new Parameter([1, 2, 3]);

        $this->assertSame(
            [
                'value' => [1, 2, 3],
                'type'  => \PDO::PARAM_STR
            ],
            $parameter->__debugInfo()
        );
    }
}
