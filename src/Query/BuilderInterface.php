<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Database\Query;

use Spiral\Database\Exception\BuilderException;
use Spiral\Database\Injection\ExpressionInterface;
use Spiral\Database\Injection\ParameterInterface;

interface BuilderInterface extends ExpressionInterface
{
    /**
     * Get ordered list of builder parameters in a form of ParameterInterface array.
     *
     * @return ParameterInterface[]
     * @throws BuilderException
     */
    public function getParameters(): array;
}