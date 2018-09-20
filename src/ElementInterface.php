<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Database;


interface ElementInterface
{
    /**
     * Get element name (unquoted).
     *
     * @return string
     */
    public function getName(): string;
}