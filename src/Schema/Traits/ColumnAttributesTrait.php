<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Schema\Traits;

use Cycle\Database\Schema\Attribute\ColumnAttribute;

trait ColumnAttributesTrait
{
    /**
     * Additional attributes.
     *
     * @var array<non-empty-string, mixed>
     */
    protected array $attributes = [];

    /**
     * @see \Cycle\Database\Schema\AbstractColumn::getInternalType()
     */
    abstract public function getInternalType(): string;

    /**
     * @param array<non-empty-string, mixed> $attributes
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = [];
        $this->fillAttributes($attributes);
        return $this;
    }

    /**
     * Get all related and additional attributes.
     *
     * @return array<non-empty-string, mixed>
     */
    public function getAttributes(): array
    {
        $result = $this->attributes;
        foreach ($this->getAttributesMap() as $field => $attribute) {
            if ($attribute->types !== null && !\in_array($this->getInternalType(), $attribute->types, true)) {
                continue;
            }
            $result[$field] = $this->$field;
        }
        return $result;
    }

    /**
     * @param non-empty-string $name
     *
     * @return bool Returns {@see true} if attribute is defined in the current driver {@see static}.
     */
    protected function isAttribute(string $name): bool
    {
        $map = $this->getAttributesMap();
        return match (true) {
            !\array_key_exists($name, $map) => false,
            $map[$name]->types === null,
            \in_array($this->getInternalType(), $map[$name]->types, true) => true,
            default => false,
        };
    }

    /**
     * @param array<non-empty-string, mixed> $attributes
     */
    protected function fillAttributes(array $attributes): void
    {
        if ($attributes === []) {
            return;
        }

        foreach ($this->getAttributesMap() as $name => $attribute) {
            if ($attribute->types !== null && !\in_array($this->getInternalType(), $attribute->types, true)) {
                continue;
            }
            if (\array_key_exists($name, $attributes)) {
                $this->$name = $attributes[$name];
                unset($attributes[$name]);
            }
        }
        $this->attributes = \array_merge($this->attributes, $attributes);
    }

    /**
     * @return array<non-empty-string, ColumnAttribute>
     */
    private function getAttributesMap(): array
    {
        // Use cache to avoid reflection on each call
        static $cache = [];
        if (isset($cache[static::class])) {
            return $cache[static::class];
        }

        $map = [];
        $reflection = new \ReflectionClass(static::class);
        foreach ($reflection->getProperties() as $property) {
            $attribute = $property->getAttributes(ColumnAttribute::class)[0] ?? null;
            if ($attribute === null) {
                continue;
            }

            $map[$property->getName()] = $attribute->newInstance();
        }
        $cache[static::class] = $map;
        return $map;
    }
}
