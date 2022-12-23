<?php

declare(strict_types=1);

namespace Cycle\Database\Config;

trait StateTrait
{
    /**
     * @throws \ReflectionException
     */
    public static function __set_state(array $properties): static
    {
        $ref = new \ReflectionClass(static::class);

        $arguments = [];
        foreach ($ref->getConstructor()?->getParameters() ?? [] as $parameter) {
            $arguments[$parameter->getName()] = \array_key_exists($parameter->getName(), $properties)
                ? $properties[$parameter->getName()]
                : $parameter->getDefaultValue();
        }

        return new static(...$arguments);
    }
}
