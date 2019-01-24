<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Database\Injection;

interface ValueInterface
{
    /**
     * Get mocked parameter value or values in array form.
     *
     * @return mixed|array
     */
    public function rawValue();
}