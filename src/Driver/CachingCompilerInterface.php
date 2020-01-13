<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Driver;

use Spiral\Database\Query\QueryParameters;

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
     * @return string
     */
    public function hashLimit(QueryParameters $params, array $tokens): string;
}
