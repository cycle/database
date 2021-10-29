<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Query;

use Cycle\Database\Injection\ParameterInterface;
use Spiral\Database\Injection\ParameterInterface as SpiralParameterInterface;

interface_exists(SpiralParameterInterface::class);

/**
 * Query parameter bindings.
 */
final class QueryParameters
{
    private $flatten = [];

    /**
     * @param ParameterInterface $parameter
     */
    public function push(SpiralParameterInterface $parameter): void
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
