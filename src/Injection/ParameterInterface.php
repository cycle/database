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
 * Parameter interface is very similar to sql fragments, however it may not only mock sql
 * expressions but also data-set of parameters to be injected into this expression.
 *
 * Usually used for complex set of parameters or late parameter binding.
 *
 * Database implementation must inject parameter SQL into expression, but use parameter value to be
 * sent to database.
 */
interface ParameterInterface extends FragmentInterface
{
    /**
     * Get mocked parameter value or values in array form.
     *
     * @return mixed|array
     */
    public function getValue();

    /**
     * Change parameter value.
     *
     * @param mixed $value
     */
    public function setValue($value);

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

    /**
     * Expand itself into array of Parameters each of what represent one nested value.
     * Attention, parameter reference LOST at this moment, make sure to alter original parmater
     * not flattened values.
     *
     * @return ParameterInterface[]
     */
    public function flatten(): array;
}
