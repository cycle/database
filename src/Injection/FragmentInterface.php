<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Database\Injection;

/**
 * Declares ability to be converted into sql statement.
 */
interface FragmentInterface
{
    /**
     * @return string
     */
    public function sqlStatement(): string;

    /**
     * @return string
     */
    public function __toString();
}