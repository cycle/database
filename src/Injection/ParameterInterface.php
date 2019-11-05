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
 * Parameter interface is very similar to sql fragments, however it may not only mock sql
 * expressions but also data-set of parameters to be injected into this expression.
 *
 * Usually used for complex set of parameters or late parameter binding.
 *
 * Database implementation must inject parameter SQL into expression, but use parameter value to be
 * sent to database.
 */
interface ParameterInterface
{
    /**
     * Get mocked parameter value or values in array form.
     *
     * @return mixed|array
     */
    public function getValue();

    /**
     * Create copy of self with new value but same type.
     *
     * @param mixed $value
     * @return self|$this
     */
    public function withValue($value): ParameterInterface;

    /**
     * Parameter type.
     *
     * @return int|mixed
     */
    public function getType();

    /**
     * Indication that parameter represent multiple values.
     *
     * @return bool
     */
    public function isArray(): bool;
}
