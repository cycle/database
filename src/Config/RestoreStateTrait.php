<?php

declare(strict_types=1);

namespace Cycle\Database\Config;

trait RestoreStateTrait
{
    /**
     * @throws \ReflectionException
     */
    public static function __set_state(array $properties): static
    {
        $ref = new \ReflectionClass(static::class);

        $arguments = [];
        foreach ($ref->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            $arguments[$name] = \array_key_exists($name, $properties)
                ? $properties[$name]
                : $parameter->getDefaultValue();
        }

        return new static(...$arguments);
    }
}
