<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Injection;

/**
 * Default implementation of ParameterInterface, provides ability to mock value or array of values
 * and automatically create valid query placeholder at moment of query compilation (? vs (?, ?, ?)).
 */
final class Parameter implements ParameterInterface
{
    /**
     * Use in constructor to automatically detect parameter type.
     */
    public const DETECT_TYPE = 900888;

    /** @var mixed|array */
    private $value = null;

    /** @var int */
    private $type = \PDO::PARAM_STR;

    /**
     * @param mixed $value
     * @param int   $type
     */
    public function __construct($value, int $type = self::DETECT_TYPE)
    {
        $this->value = $value;

        $this->resolveType($value, $type);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'value' => $this->value,
            'type'  => $this->type
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        if ($this->value instanceof ValueInterface) {
            return $this->value->rawValue();
        }

        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function withValue($value, int $type = self::DETECT_TYPE): ParameterInterface
    {
        $parameter = clone $this;
        $parameter->value = $value;
        $parameter->resolveType($value, $type);

        return $parameter;
    }

    /**
     * Parameter type.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function isArray(): bool
    {
        return is_array($this->value);
    }

    /**
     * @param mixed $value
     * @param int   $type
     */
    private function resolveType($value, int $type): void
    {
        if ($value instanceof ValueInterface) {
            $this->type = $value->rawType();
            return;
        }

        if ($type === self::DETECT_TYPE) {
            if (!is_array($value)) {
                $this->type = $this->detectType($value);
            }
        } else {
            $this->type = $type;
        }
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    private function detectType($value): int
    {
        switch (gettype($value)) {
            case 'boolean':
                return \PDO::PARAM_BOOL;
            case 'integer':
                return \PDO::PARAM_INT;
            case 'NULL':
                return \PDO::PARAM_NULL;
            default:
                return \PDO::PARAM_STR;
        }
    }
}
