<?php

/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use Spiral\Database\Injection\FragmentInterface;

abstract class BaseQueryTest extends BaseTest
{
    /**
     * Send sample query in a form where all quotation symbols replaced with { and }.
     *
     * @param string                   $query
     * @param string|FragmentInterface $fragment
     */
    protected function assertSameQuery(string $query, $fragment): void
    {
        //Preparing query
        $query = str_replace(
            ['{', '}'],
            explode('.', $this->db()->getDriver()->identifier('.')),
            $query
        );

        $this->assertSame(
            preg_replace('/\s+/', '', $query),
            preg_replace('/\s+/', '', (string)$fragment)
        );
    }
}
