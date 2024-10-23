<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Injection;

/**
 * Default implementation of ParameterInterface, provides ability to mock value or array of values
 * and automatically create valid query placeholder at moment of query compilation (? vs (?, ?, ?)).
 */
class Parameter implements ParameterInterface
{
    /**
     * Use in constructor to automatically detect parameter type.
     */
    public const DETECT_TYPE = 900888;

    /** @var array|mixed */
    private mixed $value;

    private int $type = \PDO::PARAM_STR;

    public function __construct(mixed $value, int $type = self::DETECT_TYPE)
    {
        $this->setValue($value, $type);
    }

    /**
     * Parameter type.
     */
    public function getType(): int
    {
        return $this->type;
    }

    public function setValue(mixed $value, int $type = self::DETECT_TYPE): void
    {
        $this->value = $value;

        if ($value instanceof ValueInterface) {
            $this->type = $value->rawType();
            return;
        }

        if ($type !== self::DETECT_TYPE) {
            $this->type = $type;
        } elseif (!\is_array($value)) {
            $this->type = $this->detectType($value);
        }
    }

    public function getValue(): mixed
    {
        if ($this->value instanceof ValueInterface) {
            return $this->value->rawValue();
        }

        return $this->value;
    }

    public function isArray(): bool
    {
        return \is_array($this->value);
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }

    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            'type'  => $this->type,
        ];
    }

    private function detectType(mixed $value): int
    {
        return match (\gettype($value)) {
            'boolean' => \PDO::PARAM_BOOL,
            'integer' => \PDO::PARAM_INT,
            'NULL' => \PDO::PARAM_NULL,
            default => \PDO::PARAM_STR,
        };
    }
}
