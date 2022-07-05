<?php

/**
 * This file is part of Cycle ORM package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cycle\Database\Driver;

use Cycle\Database\Query\QueryParameters;
use Spiral\Database\Query\QueryParameters as SpiralQueryParameters;
use Spiral\Database\Driver\CachingCompilerInterface as SpiralCachingCompilerInterface;

class_exists(SpiralQueryParameters::class);

/**
 * Provides the ability to calculate query hash and generate cacheble statements.
 */
interface CachingCompilerInterface extends CompilerInterface
{
    /**
     * Must return hash of the limit statement and properly set parameter values.
     *
     * @param QueryParameters $params
     * @param array           $tokens
     *
     * @return string
     */
    public function hashLimit(SpiralQueryParameters $params, array $tokens): string;
}
\class_alias(CachingCompilerInterface::class, SpiralCachingCompilerInterface::class, false);
