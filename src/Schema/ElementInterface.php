<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Schema;

interface ElementInterface
{
    /**
     * Get element name (unquoted).
     *
     * @return string
     */
    public function getName(): string;
}
