<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Injection\ParameterInterface;

/**
 * Query parameter bindings.
 */
final class QueryParameters
{
    private $flatten = [];

    /**
     * @param ParameterInterface $parameter
     */
    public function push(ParameterInterface $parameter): void
    {
        if ($parameter->isArray()) {
            foreach ($parameter->getValue() as $value) {
                $this->flatten[] = $value;
            }
        } else {
            $this->flatten[] = $parameter;
        }
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->flatten;
    }
}
