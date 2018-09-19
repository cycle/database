<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */
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
    protected function assertSameQuery(string $query, $fragment)
    {
        //Preparing query
        $query = str_replace(
            ['{', '}'],
            explode('.', $this->database()->getDriver()->identifier('.')),
            $query
        );

        $this->assertSame(
            preg_replace('/\s+/', '', $query),
            preg_replace('/\s+/', '', (string)$fragment)
        );
    }
}