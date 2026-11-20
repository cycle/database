<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Injection;

use Cycle\Database\Injection\Parameter;
use Cycle\Database\Injection\ParameterInterface;
use PHPUnit\Framework\TestCase;

class ParameterTest
{
    #[\Testo\Test]
    public function testValue(): void
    {
        $parameter = new Parameter('value');
        \Testo\Assert::instanceOf($parameter, ParameterInterface::class);

        \Testo\Assert::same($parameter->getValue(), 'value');

        $parameter->setValue('new value');

        \Testo\Assert::same($parameter->getValue(), 'new value');
    }

    #[\Testo\Test]
    public function testType(): void
    {
        $parameter = new Parameter(123, \PDO::PARAM_INT);

        \Testo\Assert::same($parameter->getValue(), 123);
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_INT);

        $parameter->setValue(2334);

        \Testo\Assert::same($parameter->getValue(), 2334);
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_INT);
    }

    #[\Testo\Test]
    public function testAutoTyping(): void
    {
        $parameter = new Parameter('abc');
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_STR);

        $parameter = new Parameter(123);
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_INT);

        $parameter = new Parameter(null);
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_NULL);

        $parameter = new Parameter(false);
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_BOOL);

        $parameter = new Parameter(true);
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_BOOL);
    }

    /**
     * At this moment arrays values are always treated as STMP parameter.
     */
    #[\Testo\Test]
    public function testAutoTypingArrays(): void
    {
        $parameter = new Parameter([1, 2, 3]);
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_STR);

        $parameter = new Parameter(['1', '2', '3']);
        \Testo\Assert::same($parameter->getType(), \PDO::PARAM_STR);
    }

    #[\Testo\Test]
    public function testDebugInfo(): void
    {
        $parameter = new Parameter([1, 2, 3]);

        \Testo\Assert::same($parameter->__debugInfo(), [
            'value' => [1, 2, 3],
            'type' => \PDO::PARAM_STR,
        ]);
    }
}
