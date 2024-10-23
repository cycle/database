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

/**
 * Provides the ability to calculate query hash and generate cacheble statements.
 */
interface CachingCompilerInterface extends CompilerInterface
{
    /**
     * Must return hash of the limit statement and properly set parameter values.
     *
     */
    public function hashLimit(QueryParameters $params, array $tokens): string;
}
