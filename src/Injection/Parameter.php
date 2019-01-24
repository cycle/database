<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database\Injection;

/**
 * Default implementation of ParameterInterface, provides ability to mock value or array of values
 * and automatically create valid query placeholder at moment of query compilation (? vs (?, ?, ?)).
 *
 * @todo implement custom sqlStatement value?
 */
class Parameter implements ParameterInterface
{
    /**
     * Use in constructor to automatically detect parameter type.
     */
    const DETECT_TYPE = 900888;

    /**
     * Mocked value or array of values.
     *
     * @var mixed|array
     */
    private $value = null;

    /**
     * Parameter type.
     *
     * @var int
     */
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
    public function setValue($value)
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
     * {@inheritdoc}
     */
    public function flatten(): array
    {
        if (!is_array($this->value)) {
            return [clone $this];
        }

        $result = [];
        foreach ($this->value as $value) {
            if (!$value instanceof ParameterInterface) {
                //Self copy
                $value = $this->withValue($value, self::DETECT_TYPE);
            }

            $result = array_merge($result, $value->flatten());
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function sqlStatement(): string
    {
        if (is_array($this->value)) {
            //Array were mocked
            return '(' . trim(str_repeat('?, ', count($this->value)), ', ') . ')';
        }

        return '?';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->sqlStatement();
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'statement' => $this->sqlStatement(),
            'value'     => $this->value,
            'type'      => $this->type
        ];
    }

    /**
     * @param mixed $value
     * @param int   $type
     */
    protected function resolveType($value, int $type)
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
    protected function detectType($value): int
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
