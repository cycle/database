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
     * @return array|mixed
     */
    public function getValue(): mixed;

    /**
     * Parameter type.
     *
     * @return int|mixed
     */
    public function getType(): mixed;

    /**
     * Indication that parameter represent multiple values.
     *
     */
    public function isArray(): bool;

    /**
     * Indication that parameter represent NULL value.
     *
     */
    public function isNull(): bool;
}
