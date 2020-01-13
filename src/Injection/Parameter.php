<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Injection;

use PDO;

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
    private $value;

    /** @var int */
    private $type = PDO::PARAM_STR;

    /**
     * @param mixed $value
     * @param int   $type
     */
    public function __construct($value, int $type = self::DETECT_TYPE)
    {
        $this->setValue($value, $type);
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
     * Parameter type.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param mixed $value
     * @param int   $type
     */
    public function setValue($value, int $type = self::DETECT_TYPE): void
    {
        $this->value = $value;

        if ($value instanceof ValueInterface) {
            $this->type = $value->rawType();
            return;
        }

        if ($type !== self::DETECT_TYPE) {
            $this->type = $type;
        } elseif (!is_array($value)) {
            $this->type = $this->detectType($value);
        }
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
     * @return bool
     */
    public function isArray(): bool
    {
        return is_array($this->value);
    }

    /**
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->value === null;
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
                return PDO::PARAM_BOOL;
            case 'integer':
                return PDO::PARAM_INT;
            case 'NULL':
                return PDO::PARAM_NULL;
            default:
                return PDO::PARAM_STR;
        }
    }
}
