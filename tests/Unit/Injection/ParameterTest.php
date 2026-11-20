<?php

declare(strict_types=1);

namespace Cycle\Database\Tests\Unit\Injection;

use Cycle\Database\Injection\Parameter;
use Cycle\Database\Injection\ParameterInterface;
use Cycle\Database\Injection\ValueInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ParameterTest
{
    public function testValue(): void
    {
        $parameter = new Parameter('value');
        Assert::instanceOf($parameter, ParameterInterface::class);

        Assert::same($parameter->getValue(), 'value');

        $parameter->setValue('new value');

        Assert::same($parameter->getValue(), 'new value');
    }

    public function testType(): void
    {
        $parameter = new Parameter(123, \PDO::PARAM_INT);

        Assert::same($parameter->getValue(), 123);
        Assert::same($parameter->getType(), \PDO::PARAM_INT);

        $parameter->setValue(2334);

        Assert::same($parameter->getValue(), 2334);
        Assert::same($parameter->getType(), \PDO::PARAM_INT);
    }

    public function testAutoTyping(): void
    {
        $parameter = new Parameter('abc');
        Assert::same($parameter->getType(), \PDO::PARAM_STR);

        $parameter = new Parameter(123);
        Assert::same($parameter->getType(), \PDO::PARAM_INT);

        $parameter = new Parameter(null);
        Assert::same($parameter->getType(), \PDO::PARAM_NULL);

        $parameter = new Parameter(false);
        Assert::same($parameter->getType(), \PDO::PARAM_BOOL);

        $parameter = new Parameter(true);
        Assert::same($parameter->getType(), \PDO::PARAM_BOOL);
    }

    /**
     * At this moment arrays values are always treated as STMP parameter.
     */
    public function testAutoTypingArrays(): void
    {
        $parameter = new Parameter([1, 2, 3]);
        Assert::same($parameter->getType(), \PDO::PARAM_STR);

        $parameter = new Parameter(['1', '2', '3']);
        Assert::same($parameter->getType(), \PDO::PARAM_STR);
    }

    public function valueObjectSuppliesRawValueAndType(): void
    {
        $value = new class implements ValueInterface {
            public function rawValue(): string
            {
                return '\x01\x02';
            }

            public function rawType(): int
            {
                return \PDO::PARAM_LOB;
            }
        };

        $parameter = new Parameter($value, \PDO::PARAM_INT);

        Assert::same($parameter->getValue(), '\x01\x02');
        Assert::same($parameter->getType(), \PDO::PARAM_LOB);
    }

    public function testDebugInfo(): void
    {
        $parameter = new Parameter([1, 2, 3]);

        Assert::same($parameter->__debugInfo(), [
            'value' => [1, 2, 3],
            'type' => \PDO::PARAM_STR,
        ]);
    }
}
